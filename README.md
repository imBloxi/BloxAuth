# BloxAuth License Management System

BloxAuth is a robust license management system designed for Roblox game developers. It provides secure authentication and licensing functionality to protect your Roblox games from unauthorized use.
![BloxiAuth Logo](https://i.ibb.co/9vDNBzf/bloxauth.jpg)

## Table of Contents

- [Features](#features)
- [Setup](#setup)
- [API Reference](#api-reference)
- [Usage](#usage)
- [Product Redesign](#product-redesign)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

## Features

- Secure license key validation
- User authentication 
- Free trial management
- Usage analytics and reporting
- In-app messaging and notifications
- Integration with Sellix.io for secure payments

## Setup

1. Clone the repository:
   ```
   git clone https://github.com/imBloxi/BloxAuth.git
   ```

2. Set up your database:
   - Create a MySQL database
   - Import the schema from `db.sql`

3. Configure your environment variables:
   - Copy `.env.example` to `.env`
   - Fill in your database credentials and other configuration options

4. Ensure your web server is configured to run PHP scripts

5. Set up a virtual host pointing to the `public` directory

## API Reference

### Validate License Key
POST /api/validate_key.php
```json
{
"license_key": "YOUR_LICENSE_KEY",
"roblox_id": "ROBLOX_USER_ID",
"place_id": "ROBLOX_PLACE_ID"
}
```
Response:
```json
{
"status": "success",
"message": "License validated successfully."
}
```

### Start Free Trial
POST /api/start_free_trial.php

Request body:
```json

Request body:
{
"user_id": "USER_ID",
"trial_days": 14
}
```

## Usage

### Roblox Script Integration

Include the following script in your Roblox game:
```lua
lua
local HttpService = game:GetService("HttpService")
local Players = game:GetService("Players")
local User_License_Key = 'YOUR_LICENSE_KEY_HERE'
local function validate_license()
local player = Players.LocalPlayer
local roblox_id = player.UserId
local place_id = game.PlaceId
local is_https = string.sub(game:GetService("HttpService").Url, 1, 5) == "https"
if not is_https then
player:Kick("This place does not have HTTPS enabled.")
return
end
local url = "https://your-domain.com/api/validate_key.php"
local data = {
license_key = User_License_Key,
roblox_id = tostring(roblox_id),
place_id = tostring(place_id)
}
local jsonData = HttpService:JSONEncode(data)
local response = HttpService:PostAsync(url, jsonData, Enum.HttpContentType.ApplicationJson)
local result = HttpService:JSONDecode(response)
if result.status == "success" then
print("License validated successfully.")
else
player:Kick("License validation failed: " .. result.message)
end
end
validate_license()
```

## Product Redesign

We recently underwent a product redesign to improve user experience and add new features. Key changes include:

- Updated user interface for better usability
- Enhanced analytics dashboard
- Improved onboarding process for new users
- Integration of free trial system

Our redesign process followed these steps:

1. Defined business goals and objectives
2. Conducted user research to identify areas for improvement
3. Paired user research with product usage analytics
4. Created prototypes and validated concepts with real users
5. Ran marketing campaigns to prepare users for the release
6. Launched the redesigned product to our audience
7. Implemented user onboarding for the new design
8. Continuously gathered user feedback for further improvements

For more details on our redesign process, see [this guide on product redesign](https://userpilot.com/blog/product-redesign/) [1].

## Troubleshooting

If you encounter issues with Google Play compliance, follow these steps:

1. Go to your Play Console
2. Select the app
3. Go to App bundle explorer
4. Select the non-compliant APK version
5. Create a new release with the policy-compliant version
6. Ensure the non-compliant version is under the "Not included" section
7. Submit the update for review

For more detailed instructions, refer to [this Stack Overflow answer](https://stackoverflow.com/a/67796763) [2].

## Contributing

We welcome contributions to BloxAuth! Please read our [CONTRIBUTING.md](CONTRIBUTING.md) file for guidelines on how to submit pull requests, report issues, and suggest improvements.

## License

This project is licensed under the Apache 2.0 - see the [LICENSE.md](LICENSE.md) file for details.


