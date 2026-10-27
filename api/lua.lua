-- BloxAuth license check for a Roblox game. Put this in a server Script.
-- Requires Game Settings > Security > "Allow HTTP Requests".

local HttpService = game:GetService("HttpService")
local Players = game:GetService("Players")

local BASE_URL = "https://your-domain.com/api" -- your BloxAuth install
local LICENSE_KEY = "your_license_key_here"

local CHECK_INTERVAL = 300 -- seconds between re-checks

-- The game's owner: a user ID for user-owned games, a group ID for group games.
local OWNER_ID = game.CreatorId

local function post(path, contentType, body)
	local ok, response = pcall(function()
		return HttpService:RequestAsync({
			Url = BASE_URL .. path,
			Method = "POST",
			Headers = { ["Content-Type"] = contentType },
			Body = body,
		})
	end)
	if not ok then
		return nil, tostring(response)
	end
	return response
end

local function validateLicense(recheck)
	local body = HttpService:JSONEncode({
		license_key = LICENSE_KEY,
		roblox_id = tostring(OWNER_ID),
		place_id = tostring(game.PlaceId),
		recheck = recheck == true, -- re-checks don't use up max_uses
	})
	local response, err = post("/validate_key.php", "application/json", body)
	if not response then
		warn("BloxAuth: license check failed: " .. err)
		return false
	end

	local ok, result = pcall(HttpService.JSONDecode, HttpService, response.Body)
	if ok and result.status == "success" then
		return true
	end
	warn("BloxAuth: license rejected: " .. tostring(ok and result.message or response.StatusCode))
	return false
end

local function logUsage(userId, action)
	local body = HttpService:JSONEncode({
		license_key = LICENSE_KEY,
		roblox_user_id = userId,
		roblox_place_id = game.PlaceId,
		action = action,
	})
	local response, err = post("/api.php?action=log_usage", "application/json", body)
	if not response or response.StatusCode ~= 200 then
		warn("BloxAuth: failed to log usage: " .. tostring(err or response.StatusCode))
	end
end

local function onInvalidLicense()
	-- Decide what an unlicensed server should do, e.g. kick players or disable features.
	warn("BloxAuth: invalid license, the game will not function properly.")
end

Players.PlayerAdded:Connect(function(player)
	logUsage(player.UserId, "join")
end)

Players.PlayerRemoving:Connect(function(player)
	logUsage(player.UserId, "leave")
end)

if not validateLicense() then
	onInvalidLicense()
end

while true do
	task.wait(CHECK_INTERVAL)
	if not validateLicense(true) then
		onInvalidLicense()
	end
end
