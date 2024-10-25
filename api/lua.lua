local HttpService = game:GetService("HttpService")
local Players = game:GetService("Players")

local API_URL = "https://your-api-url.com/api/v1/licenses"
local LICENSE_KEY = "your_license_key_here"

local function logUsage(userId, action)
    local data = {
        license_key = LICENSE_KEY,
        roblox_user_id = userId,
        roblox_place_id = game.PlaceId,
        action = action
    }

    local success, response = pcall(function()
        return HttpService:RequestAsync({
            Url = API_URL .. "/log_usage",  -- Adjust the endpoint URL
            Method = "POST",
            Headers = {
                ["Content-Type"] = "application/json",
                ["Authorization"] = "Bearer " .. LICENSE_KEY
            },
            Body = HttpService:JSONEncode(data)
        })
    end)

    if success then
        if response.StatusCode == 200 then
            print("Successfully logged usage for user " .. userId)
        else
            warn("Failed to log usage. Status code: " .. response.StatusCode)
        end
    else
        warn("Failed to log usage: " .. tostring(response))
    end
end

local function validateLicense()
    local data = {
        license_key = LICENSE_KEY,
        roblox_place_id = game.PlaceId
    }

    local success, response = pcall(function()
        return HttpService:RequestAsync({
            Url = API_URL .. "/validate_license",  -- Adjust the endpoint URL
            Method = "POST",
            Headers = {
                ["Content-Type"] = "application/json",
                ["Authorization"] = "Bearer " .. LICENSE_KEY
            },
            Body = HttpService:JSONEncode(data)
        })
    end)

    if success and response.StatusCode == 200 then
        local result = HttpService:JSONDecode(response.Body)
        return result.status == "success"
    else
        warn("License validation failed. Status code: " .. (response and response.StatusCode or "nil"))
        return false
    end
end

Players.PlayerAdded:Connect(function(player)
    logUsage(player.UserId, "join")
end)

Players.PlayerRemoving:Connect(function(player)
    logUsage(player.UserId, "leave")
end)

-- Validate license on server start
if not validateLicense() then
    warn("Invalid license! The game will not function properly.")
    -- Implement your desired behavior for invalid license (e.g., kick all players, disable features)
end

-- Periodically validate license and log usage
while true do
    wait(300) -- Check every 5 minutes
    if not validateLicense() then
        warn("License has become invalid! The game will not function properly.")
        -- Implement your desired behavior for invalid license
    end
    -- Log current player count
    logUsage(#Players:GetPlayers(), "player_count")
end
