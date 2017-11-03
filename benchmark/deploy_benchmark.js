const fs = require('fs');

if (process.argv.length !== 3) {
    console.error("Usage: node deploy_benchmark.js CONFIG");
    process.exit(1);
}

const config = JSON.parse(fs.readFileSync(process.argv[2]));
console.log(config);

// Generate .env file
    // Publish URLs

// Create out/ and resources/ if necessary

// Generate lpd config

// Generate data

// Generate resources: oldest timestamps, static data

// Deploy php -S