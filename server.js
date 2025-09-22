const express = require('express');
const fs = require('fs');
const path = require('path');
const app = express();
const PORT = 3000;

app.use(express.json());
app.use(express.static('public'));

const jsonFile = path.join(__dirname, 'children-records.json');

app.post('/api/add-child', (req, res) => {
    let children = [];
    if (fs.existsSync(jsonFile)) {
        const json = fs.readFileSync(jsonFile, 'utf-8');
        try {
            children = JSON.parse(json);
        } catch {
            children = [];
        }
    }

    children.push(req.body);

    fs.writeFileSync(jsonFile, JSON.stringify(children, null, 2));

    res.json({
        success: true,
        downloadUrl: '/children-records.json'
    });
});

app.get('/children-records.json', (req, res) => {
    res.download(jsonFile, 'children-records.json');
});

app.listen(PORT, () => {
    console.log(`Server running on http://localhost:${PORT}`);
});
