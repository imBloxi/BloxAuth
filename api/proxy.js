const express = require('express');
const axios = require('axios');
const app = express();
const port = 3000;

app.use(express.json());

app.get('/place-details/:placeId', async (req, res) => {
    const { placeId } = req.params;
    try {
        const response = await axios.get(`https://games.roblox.com/v1/games/multiget-place-details?placeIds=${placeId}`);
        res.json(response.data);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

app.get('/group-details/:groupId', async (req, res) => {
    const { groupId } = req.params;
    try {
        const response = await axios.get(`https://groups.roblox.com/v1/groups/${groupId}`);
        res.json(response.data);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

app.listen(port, () => {
    console.log(`Proxy server listening at http://localhost:${port}`);
});
