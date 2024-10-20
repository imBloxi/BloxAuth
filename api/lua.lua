local HttpService = game:GetService("HttpService")

local function getPlaceId()
    return game.PlaceId
end

local function getGroupId(userId)
    local url = "https://groups.roblox.com/v1/users/" .. userId .. "/groups/roles"
    local success, result = pcall(function()
        return HttpService:GetAsync(url)
    end)

    if success then
        local data = HttpService:JSONDecode(result)
        if data.data and #data.data > 0 then
            -- Assuming the first group is the one you want
            return data.data[1].group.id
        else
            warn("User is not in any group.")
            return nil
        end
    else
        warn("Failed to fetch group ID: " .. result)
        return nil
    end
end

local function getDeveloperId()
    return game.CreatorId
end

local function validateLicense(licenseKey, sellerId)
    local userId = game.Players.LocalPlayer.UserId
    local groupId = getGroupId(userId)
    local placeId = getPlaceId()
    local developerId = getDeveloperId()

    if not groupId then
        warn("Cannot validate license without a group ID.")
        return
    end

    local url = "https://yourdomain.com/api/api.php"
    local data = {
        license_key = licenseKey,
        place_id = placeId,
        group_id = groupId,
        developer_id = developerId,
        seller_id = sellerId
    }
    
    local jsonData = HttpService:JSONEncode(data)
    
    local success, response = pcall(function()
        return HttpService:PostAsync(url, jsonData, Enum.HttpContentType.ApplicationJson)
    end)

    if success then
        local result = HttpService:JSONDecode(response)
        if result.status == "success" then
            print("License is valid.")
            -- Additional logic for valid license
        else
            warn("License validation failed: " .. result.message)
            -- Handle invalid license
        end
    else
        warn("Failed to validate license: " .. response)
    end
end

-- Example usage
validateLicense("your-license-key", "your-seller-id")