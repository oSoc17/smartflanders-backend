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
let env = "";
let datasets = 'DATASETS="';
outdirs.forEach(dir => {
    let caps = dir.toUpperCase();
    env += caps + '_PUBLISH="http://' + dir + '.' + config["deploy:base_url"] + '"\n';
    datasets += caps + ',';
    env += caps + '_PATH=oSoc\\Smartflanders\\Datasets\\Benchmark\\Benchmark' + '\n';
});
env += datasets + '"\n';
env += 'RANGE_GATES_CONFIG="' + config["deploy:rangegate_config"] + '"\n';
env += 'DATA_DIR="benchmark/out"\n';
env += 'RESOURCE_DIR="benchmark/resources"\n';
env += 'DEFAULT_GATHER_INTERVAL=' + config["time:time_per_file"] + '\n'; // TODO this is not right in .env!
env += 'BASE_PUBLISH="' + config["deploy:base_url"] + '"\n';

fs.writeFileSync('../.env.benchmark', env);

// Generate resources: oldest timestamps, static data

// Deploy php -S