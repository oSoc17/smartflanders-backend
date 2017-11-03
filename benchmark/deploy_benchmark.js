const fs = require('fs');
const childProcess = require('child_process');
const path = require('path');

if (process.argv.length !== 3) {
    console.error("Usage: node deploy_benchmark.js CONFIG");
    process.exit(1);
}

const config = JSON.parse(fs.readFileSync(process.argv[2]));

// Create out/ and resources/ if necessary
if (!fs.existsSync('out')) {
    fs.mkdirSync('out');
}
if (!fs.existsSync('resources')) {
    fs.mkdirSync('resources');
}

// Cleanup
let outdirs = fs.readdirSync('out');
let resourcefiles = fs.readdirSync('resources');
outdirs.forEach(dir => {
    let files = fs.readdirSync(path.join('out', dir));
    files.forEach(file => {
        fs.unlinkSync(path.join('out', dir, file), err => {
            if (err) console.error(err);
        })
    });
});
resourcefiles.forEach(file => {
    fs.unlinkSync(path.join('resources', file), err => {
        if (err) console.error(err);
    })
});

// Generate lpd config
config["file:output"] = 'out';
config["file:output_meta_data"] = false;
config["file:extension"] = '';
config["file:name_format"] = "UNIX";
fs.writeFileSync('lpd_config.json', JSON.stringify(config));

// Generate data
let script = config['deploy:lpdgen_location'] + '/generator.js';
let genProc = childProcess.fork(script, ['lpd_config.json']);
genProc.on('exit', code => console.log("Process exited with exit code", code));

// Generate .env file
    // Publish URLs
    // Datasets, Datasets_gather
    // Paths: oSoc\Smartflanders\Datasets\Benchmark\Benchmark
    // Rangegate config === config.rangegate_config
    // Data, resource dir: benchmark/out, benchmark/resources
    // Default gather interval: arbitrary
    // base_publish === config.base_url

// Generate resources: oldest timestamps, static data

// Deploy php -S