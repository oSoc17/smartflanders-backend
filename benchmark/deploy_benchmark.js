const fs = require('fs');
const childProcess = require('child_process');
const path = require('path');
const N3 = require('n3');

if (process.argv.length !== 3) {
    console.error("Usage: node deploy_benchmark.js CONFIG");
    process.exit(1);
}

const config = JSON.parse(fs.readFileSync(process.argv[2]));

const buildingBlocks = {
    'ParkingSite': 'http://vocab.datex.org/terms#UrbanParkingSite',
    'pLabel': 'http://www.w3.org/2000/01/rdf-schema#label',
    'pNumberOfSpaces': 'http://vocab.datex.org/terms#parkingNumberOfSpaces',
    'pRdfType': 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
};

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
genProc.on('exit', code => {
    console.log("Process exited with exit code", code);

    outdirs = fs.readdirSync('out');
    resourcefiles = fs.readdirSync('resources');

    // Generate .env file
    let env = "";
    let datasets = 'DATASETS="';
    outdirs.forEach(dir => {
        let caps = dir.toUpperCase();
        env += caps + '_PUBLISH="http://' + dir + '.' + config["deploy:base_url"] + '"\n';
        datasets += caps + ',';
        env += caps + '_PATH=oSoc\\Smartflanders\\Datasets\\Benchmark\\Benchmark' + '\n';
    });
    env += datasets.slice(0,-1) + '"\n';
    env += 'DATASETS_GATHER=""\n';
    env += 'RANGE_GATES_CONFIG="' + config["deploy:rangegate_config"] + '"\n';
    env += 'DATA_DIR="benchmark/out"\n';
    env += 'RESOURCE_DIR="benchmark/resources"\n';
    env += 'DEFAULT_GATHER_INTERVAL=' + config["time:time_per_file"] + '\n';
    env += 'BASE_PUBLISH="' + config["deploy:base_url"] + '"\n';

    fs.writeFileSync('../.env.benchmark', env);

    // Generate resources: oldest timestamps, static data
    outdirs.forEach(dir => {
        let hasStaticData = false;
        let staticData = {triples: [], prefixes: []};
        const files = fs.readdirSync(path.join('out', dir));
        files.forEach(file => {
            let contents = fs.readFileSync(path.join('out', dir, file), "utf8");
            let foundStaticData = false;
            let realTimeData = {triples: [], prefixes: []};
            let triples = N3.Parser().parse(contents);
            triples.forEach(t => {
                    if ((t.predicate === buildingBlocks.pRdfType && t.object === buildingBlocks.ParkingSite) ||
                        t.predicate === buildingBlocks.pLabel || t.predicate === buildingBlocks.pNumberOfSpaces) {
                        if (!hasStaticData) {
                            foundStaticData = true;
                            staticData.triples.push(t);
                        }
                    } else {
                        realTimeData.triples.push(t);
                    }
                });
            if (foundStaticData) {
                hasStaticData = true;
                let filename = path.join('resources', dir + '_static_data');
                let writer = N3.Writer();
                writer.addTriples(staticData.triples);
                writer.addPrefixes(staticData.prefixes);
                writer.end((e, r) => {
                    fs.writeFileSync(filename, r)
                });
            }
            let writer = N3.Writer();
            writer.addTriples(realTimeData.triples);
            writer.addPrefixes(realTimeData.prefixes);
            writer.end((e, r) => {
                fs.writeFileSync(path.join('out', dir, file), r)
            });
        })
    });

    // Deploy php -S
});