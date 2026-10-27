#!/bin/bash
# End-to-end checks against a running BloxAuth server and its database.
# Usage: tests/e2e.sh   (see "Testing" in README.md)
# Env: BASE_URL (default http://127.0.0.1:8099), DB_HOST, DB_USER, DB_PASS, DB_NAME
B=${BASE_URL:-http://127.0.0.1:8099}
SP=$(mktemp -d)
trap 'rm -rf "$SP"' EXIT
PASS=0; FAIL=0
ok()   { PASS=$((PASS+1)); echo "  ok   $1"; }
bad()  { FAIL=$((FAIL+1)); echo "  FAIL $1 :: $2"; }
expect() { # name, haystack, needle
  if grep -q -- "$3" <<<"$2"; then ok "$1"; else bad "$1" "$(head -c 300 <<<"$2")"; fi; }
sql() { mysql -N -h "${DB_HOST:-localhost}" -u "${DB_USER:-root}" ${DB_PASS:+-p"$DB_PASS"} "${DB_NAME:-roblox_licensing}" -e "$1"; }

sql "delete from rate_limits"   # start each run with fresh limits
U=e2e_$RANDOM; J=$SP/jar_$U.txt; rm -f $J
c() { curl -s -b $J -c $J "$@"; }

echo "== signup and setup"
r=$(c -o /dev/null -w '%{http_code} %{redirect_url}' -d "username=$U&email=$U@x.test&password=hunter2hunter2&confirm_password=hunter2hunter2&agree_tos=1" $B/auth/register.php)
expect "register redirects to setup" "$r" "302 .*app/setup_app.php"
r=$(c -o /dev/null -w '%{redirect_url}' $B/app/dashboard.php)
expect "dashboard sends new user to setup" "$r" "setup_app.php"
r=$(c -o /dev/null -w '%{redirect_url}' -d "app_name=My <b>Game</b>&app_description=d&app_category=education" $B/app/setup_app.php)
expect "setup redirects to dashboard" "$r" "app/dashboard.php"
r=$(c $B/app/dashboard.php)
expect "dashboard shows escaped app name" "$r" "My &lt;b&gt;Game&lt;/b&gt;"
UID_=$(sql "select id from users where username='$U'")
KEY=$(sql "select api_key from api_keys where user_id=$UID_")
[ -n "$KEY" ] && ok "api key created" || bad "api key created" "none"

echo "== license UI"
r=$(c -L -d "create_license=1&whitelist_type=user&roblox_user_id=111&description=ui&valid_until=&max_uses=" $B/app/license.php)
expect "create user license in UI" "$r" "License created:"
UIKEY=$(sql "select \`key\` from licenses_new where user_id=$UID_ and description='ui'")
r=$(c -L -d "create_license=1&whitelist_type=place&whitelist_id=&roblox_user_id=" $B/app/license.php)
expect "place license needs place id" "$r" "Enter the numeric place ID"
r=$(c "$B/app/license.php?search=ui&whitelist_type=user")
expect "search finds license" "$r" "$UIKEY"
LID=$(sql "select id from licenses_new where \`key\`='$UIKEY'")
r=$(c -L -d "edit_license=1&license_id=$LID&description=edited&valid_until=2030-01-01T10:00&max_uses=&custom_tier=Pro&webhook_url=" $B/app/license.php)
expect "edit license" "$r" "License updated"
expect "edit saved" "$(sql "select description, valid_until, custom_tier from licenses_new where id=$LID")" "edited.2030-01-01 10:00:00.Pro"

echo "== validate API"
v() { curl -s -d "$1" $B/api/validate_key.php; }
expect "valid user license" "$(v "license_key=$UIKEY&roblox_id=111&place_id=5")" '"status":"success"'
expect "wrong roblox id rejected" "$(v "license_key=$UIKEY&roblox_id=222&place_id=5")" 'failure'
expect "unknown key rejected" "$(v "license_key=nope&roblox_id=111")" 'failure'
expect "usage logged" "$(sql "select count(*) from usage_logs where license_id=$LID")" "^1$"
expect "use counted" "$(sql "select current_uses from licenses_new where id=$LID")" "^1$"
expect "JSON body accepted" "$(curl -s -H 'Content-Type: application/json' -d "{\"license_key\":\"$UIKEY\",\"roblox_id\":\"111\",\"recheck\":true}" $B/api/validate_key.php)" '"status":"success"'
expect "recheck does not count a use" "$(sql "select current_uses from licenses_new where id=$LID")" "^1$"

echo "== generate API"
g() { curl -s -w ' %{http_code}' -H "X-API-Key: $1" -H 'Content-Type: application/json' -d "$2" $B/api/generate_license.php; }
expect "no key -> 401" "$(curl -s -w ' %{http_code}' -X POST $B/api/generate_license.php)" "401"
expect "bad key -> 401" "$(g bad '{}')" "Invalid API key.*401"
r=$(g $KEY '{"whitelist_type":"place","whitelist_id":"17625359962","max_uses":2,"description":"api"}')
expect "create place license" "$r" '"success":true.*201'
PKEY=$(sed 's/.*"license_key":"\([^"]*\)".*/\1/' <<<"$r")
expect "place license valid in place" "$(v "license_key=$PKEY&roblox_id=1&place_id=17625359962")" success
expect "place license wrong place" "$(v "license_key=$PKEY&roblox_id=1&place_id=999")" "not valid for this place"
v "license_key=$PKEY&roblox_id=1&place_id=17625359962" >/dev/null
expect "max_uses enforced" "$(v "license_key=$PKEY&roblox_id=1&place_id=17625359962")" "usage limit"
r=$(g $KEY '{"roblox_user_id":"42","valid_until":"2001-01-01"}')
EKEY=$(sed 's/.*"license_key":"\([^"]*\)".*/\1/' <<<"$r")
expect "expired rejected" "$(v "license_key=$EKEY&roblox_id=42")" "expired"
r=$(g $KEY '{"whitelist_type":"group","whitelist_id":"7"}')
GKEY=$(sed 's/.*"license_key":"\([^"]*\)".*/\1/' <<<"$r")
expect "group license valid for group owner" "$(v "license_key=$GKEY&roblox_id=7&place_id=1")" success
expect "bad whitelist_type -> 400" "$(g $KEY '{"whitelist_type":"planet"}')" "400"

echo "== legacy keys and delete"
sql "insert into licenses (user_id, roblox_id, place_id, \`key\`, whitelist_type) values ($UID_, '555', '', 'legacy_$U', 'user')"
expect "legacy key still validates" "$(v "license_key=legacy_$U&roblox_id=555&place_id=1")" success
expect "legacy key wrong user" "$(v "license_key=legacy_$U&roblox_id=556")" failure
DID=$(sql "select id from licenses_new where \`key\`='$GKEY'")
r=$(c -L -d "delete_license=1&license_id=$DID" $B/app/license.php)
expect "delete license with logs" "$r" "License deleted"
expect "deleted license gone" "$(sql "select count(*) from licenses_new where id=$DID")" "^0$"

echo "== game API (lua client)"
a() { curl -s -H 'Content-Type: application/json' -d "$2" "$B/api/api.php?action=$1"; }
expect "api.php validate" "$(a validate_license "{\"license_key\":\"$UIKEY\",\"roblox_user_id\":111,\"roblox_place_id\":5}")" success
expect "api.php log_usage" "$(a log_usage "{\"license_key\":\"$UIKEY\",\"roblox_user_id\":9,\"action\":\"join\"}")" success
expect "api.php log_usage bad key" "$(a log_usage '{"license_key":"x","action":"join"}')" "Invalid license key"
expect "log_usage.php needs real key" "$(curl -s -d 'license_key=x&roblox_id=1' $B/api/log_usage.php)" "Invalid license key"

echo "== v1 management API"
m() { curl -s -w ' %{http_code}' -H "Authorization: Bearer $KEY" "$@"; }
expect "v1 list" "$(m "$B/api/v1/licenses/index.php?type=list")" "$PKEY"
expect "v1 renew" "$(m -X PUT -d "{\"license_id\":$LID,\"new_valid_until\":\"2031-01-01\"}" "$B/api/v1/licenses/index.php?type=renew")" '"success":true} 200'
expect "v1 revoke" "$(m -X DELETE "$B/api/v1/licenses/index.php?type=revoke&license_id=$LID")" '"success":true} 200'
expect "revoked key rejected" "$(v "license_key=$UIKEY&roblox_id=111")" "revoked"
expect "whitelist.php reports revoked" "$(curl -s -H "Authorization: $KEY" -d "{\"license_key\":\"$UIKEY\"}" $B/api/whitelist.php)" "Revoked"
expect "v1 no token -> 401" "$(curl -s -w ' %{http_code}' "$B/api/v1/licenses/index.php?type=list")" "401"

echo "== isolation between users"
U2=e2e2_$RANDOM; J2=$SP/jar_$U2.txt
curl -s -b $J2 -c $J2 -o /dev/null -d "username=$U2&email=$U2@x.test&password=hunter2hunter2&confirm_password=hunter2hunter2&agree_tos=1" $B/auth/register.php
curl -s -b $J2 -c $J2 -o /dev/null -d "app_name=Other" $B/app/setup_app.php
U2ID=$(sql "select id from users where username='$U2'"); KEY2=$(sql "select api_key from api_keys where user_id=$U2ID")
PID=$(sql "select id from licenses_new where \`key\`='$PKEY'")
expect "other user's key can't revoke" "$(curl -s -w ' %{http_code}' -H "X-API-Key: $KEY2" -X DELETE "$B/api/v1/licenses/index.php?type=revoke&license_id=$PID")" "404"
curl -s -b $J2 -c $J2 -o /dev/null -d "revoke_license=1&license_id=$PID" $B/app/license.php
expect "other user's form can't revoke" "$(sql "select is_revoked from licenses_new where id=$PID")" "^0$"
expect "license_usage.php ownership" "$(curl -s -b $J2 "$B/api/license_usage.php?license_id=$PID")" "not found"
expect "license_usage.php owner ok" "$(c "$B/api/license_usage.php?license_id=$PID")" '"status":"success"'

echo "== staff pages"
for p in admin/index.php admin/issue_keys.php app/moderation.php app/staff.php app/review_applications.php billing/verify_payment.php; do
  expect "non-staff blocked: $p" "$(c -s -w ' %{http_code}' $B/$p)" "403"
done
sql "update users set is_staff=1 where id=$UID_"
for p in admin/index.php admin/issue_keys.php app/moderation.php app/staff.php app/review_applications.php; do
  expect "staff allowed: $p" "$(c -s -o /dev/null -w '%{http_code}' $B/$p)" "200"
done
expect "issue key" "$(c -d x=1 $B/admin/issue_keys.php)" "New license key generated"
sql "insert into payments (user_id, credits, amount, currency, transaction_id, status) values ($U2ID, 50, 0, 'ROBUX', 'tx_e2e_$U2', 'pending')"
expect "staff confirms payment" "$(c -d "transaction_id=tx_e2e_$U2" $B/billing/verify_payment.php)" "confirmed"
expect "credits added once" "$(sql "select credits from users where id=$U2ID")" "^50$"
c -o /dev/null -d "transaction_id=tx_e2e_$U2" $B/billing/verify_payment.php
expect "no double credit" "$(sql "select credits from users where id=$U2ID")" "^50$"

echo "== ban flow"
c -o /dev/null -d "user_id=$U2ID&action=ban&reason=e2e+test" $B/admin/index.php
r=$(curl -s -b $J2 -c $J2 -o /dev/null -w '%{redirect_url}' $B/app/dashboard.php)
expect "banned user sent to notice" "$r" "not-approved"
expect "ban notice shows reason" "$(curl -s -b $J2 $B/not-approved/index.php)" "e2e test"
expect "moderation logged" "$(sql "select action from moderation_logs where user_id=$U2ID")" "ban"
c -o /dev/null -d "user_id=$U2ID&action=unban" $B/admin/index.php
expect "unbanned user back in" "$(curl -s -b $J2 -o /dev/null -w '%{http_code}' $B/app/dashboard.php)" "200"

echo "== every logged-in page renders"
for p in app/dashboard.php app/license.php app/settings.php app/user_hub.php app/sellix_products.php billing/index.php billing/select_plan.php billing/link_roblox.php obfuscate/index.php delete_account/index.php app/index.php; do
  code=$(c -s -o $SP/page.html -w '%{http_code}' $B/$p)
  if [ "$code" = 200 ] || [ "$code" = 302 ]; then ok "$p ($code)"; else bad "$p" "$code $(head -c 200 $SP/page.html)"; fi
done

echo "== settings, scripts, notifications"
r=$(c -L -d "theme=dark&timezone=Europe/Riga&discord_webhook_url=&api_rate_limit=30&sellix_api_key=&sellix_store_id=&sellix_webhook_secret=whsec&activity_retention=10&notify_on_new_license=1" $B/app/settings.php)
expect "settings saved" "$r" "Settings updated"
expect "settings stored" "$(sql "select theme, timezone, sellix_webhook_secret from users where id=$UID_")" "dark.Europe/Riga.whsec"
c -o /dev/null -d "create_script=1&script_name=Main&script_content=print(1)" $B/app/user_hub.php
SID=$(sql "select id from scripts where user_id=$UID_")
c -o /dev/null -d "create_version=1&script_id=$SID&version_name=v2&version_content=print(2)" $B/app/user_hub.php
VID=$(sql "select id from script_versions where script_id=$SID and name='v2'")
expect "switch active version" "$(c -d "update_active_version=1&script_id=$SID&version_id=$VID" $B/app/user_hub.php)" '"success":true'
NID=$(sql "insert into notifications (user_id, message) values ($UID_, 'hi'); select last_insert_id()")
curl -s -b $J2 -o /dev/null -d "notification_id=$NID" $B/app/mark_notification.php
expect "other user can't mark my notification" "$(sql "select is_read from notifications where id=$NID")" "^0$"
c -o /dev/null -d "notification_id=$NID" $B/app/mark_notification.php
expect "owner marks notification" "$(sql "select is_read from notifications where id=$NID")" "^1$"

echo "== Sellix webhook"
sql "insert into sellix_products (user_id, sellix_product_id, license_type, price, currency) values ($UID_, 'prod_$U', 'user', 5, 'USD')"
P="{\"event\":\"order:paid\",\"data\":{\"uniqid\":\"ord_$U\",\"status\":\"COMPLETED\",\"product_id\":\"prod_$U\"}}"
expect "forged signature rejected" "$(curl -s -w ' %{http_code}' -H 'X-Sellix-Signature: nope' -d "$P" $B/api/sellix_webhook.php)" "401"
SIG=$(printf '%s' "$P" | openssl dgst -sha512 -hmac whsec | awk '{print $NF}')
expect "signed webhook accepted" "$(curl -s -H "X-Sellix-Signature: $SIG" -d "$P" $B/api/sellix_webhook.php)" "processed"
curl -s -o /dev/null -H "X-Sellix-Signature: $SIG" -d "$P" $B/api/sellix_webhook.php
expect "one license per order (retry safe)" "$(sql "select count(*) from licenses_new where description like '%ord_$U%'")" "^1$"
expect "buyer notified" "$(sql "select count(*) from notifications where user_id=$UID_ and message like '%Sellix%'")" "^1$"

expect "ban unknown user doesn't crash" "$(c -s -o /dev/null -w '%{http_code}' -d "user_id=99999999&action=ban&reason=x" $B/admin/index.php)" "302"
sql "delete from rate_limits"

echo "== account lifecycle"
r=$(c -o /dev/null -w '%{redirect_url}' $B/auth/logout.php)
expect "logout" "$r" "auth/login.php"
expect "logged out" "$(c -o /dev/null -w '%{redirect_url}' $B/app/dashboard.php)" "auth/login.php"
r=$(c -o /dev/null -w '%{redirect_url}' -d "username=$U&password=wrongwrong" $B/auth/login.php)
expect "wrong password stays on login" "$(c -d "username=$U&password=wrongwrong" $B/auth/login.php)" "Invalid username or password"
r=$(c -o /dev/null -w '%{redirect_url}' -d "username=$U&password=hunter2hunter2&remember_me=1" $B/auth/login.php)
expect "login" "$r" "app/dashboard.php"
grep -q remember_token $J && ok "remember cookie set" || bad "remember cookie set" ""
# a fresh session with only the remember cookie
grep remember_token $J > $SP/rm_$U.txt; sed -i 's/^#HttpOnly_//' $SP/rm_$U.txt
TOKEN=$(awk '{print $NF}' $SP/rm_$U.txt)
expect "remember me logs back in" "$(curl -s -o /dev/null -w '%{http_code}' -b "remember_token=$TOKEN" $B/app/dashboard.php)" "200"
c -o /dev/null -d "confirm_delete=1&delete_reason=e2e" $B/delete_account/index.php
expect "deleted account can't log in" "$(c -d "username=$U&password=hunter2hunter2" $B/auth/login.php)" "Invalid username"
expect "deleted account remember cookie dead" "$(curl -s -o /dev/null -w '%{redirect_url}' -b "remember_token=$TOKEN" $B/app/dashboard.php)" "login"

echo "== rate limits"
sql "delete from rate_limits"
for i in 1 2 3 4 5; do curl -s -o /dev/null -d "username=nobody&password=x" $B/auth/login.php; done
expect "login rate limited" "$(curl -s -d "username=nobody&password=x" $B/auth/login.php)" "Too many login attempts"
sql "delete from rate_limits"

echo
echo "PASS=$PASS FAIL=$FAIL"
[ "$FAIL" -eq 0 ]
