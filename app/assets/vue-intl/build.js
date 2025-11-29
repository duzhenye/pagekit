const fs = require('fs');
const {resolve} = require('path');
const rollup = require('rollup');
const uglify = require('uglify-js');
const json = require('@rollup/plugin-json');
const {babel} = require('@rollup/plugin-babel');
const {promisify} = require('util');
const package = require('./package.json');
const version = process.env.VERSION || package.version;
const banner =
    "/*!\n" +
    " * vue-intl v" + version + "\n" +
    " * Released under the MIT License.\n" +
    " */\n";

const readFile = promisify(fs.readFile);
const read = async function (file, cb) {

    const data = await readFile(file, 'utf8');
    cb && cb(data);
    return data;

};

const logFile = async function (file) {
    const data = await read(file);
    console.log(`${cyan(file)} ${getSize(data)}`);
};

const writeFile = promisify(fs.writeFile);
const write = async function (dest, data) {

     const err = await writeFile(dest, data);

     if (err) {
        console.log(err);
        throw err;
    }

     logFile(dest);

     return dest;

};

run().catch(({message}) => {
	console.error(message);
	process.exitCode = 1;
});

async function run() {
    const files = [{
        name: 'vue-intl',
        configs: [{ format: 'umd', banner, name: 'VueIntl', sourcemap: false  }, { format: 'cjs', banner }],
        minify: true
    }];

	return Promise.all(files.map(async (file) => {
		let { name, configs, minify } = file;
		return compile('src/index.js', `dist/${name}`, { configs, minify: !!minify })
	}));
};

async function compile (file, dest, {name, configs, minify = true}) {
	name = (name || '').replace(/[^\w]/g, '_');

    const bundle = await rollup.rollup({
        input: resolve(file),
        plugins: [json(), babel({ exclude: 'node_modules/**', babelHelpers: 'bundled' })]
    });

    return configs.map(async (config) => {
        let {output: [{code, map}]} = await bundle.generate(config);
        code = code.replace(/(>)\\n\s+|\\n\s+(<)/g, '$1 $2');

        return [
            config.format === 'umd' ? [write(`${dest}.js`, code), minify ? write(`${dest}.min.js`, uglify.minify(code, {output: {preamble: banner}}).code) : null] : null,
            config.format === 'cjs' ? write(`${dest}.common.js`, code) : null
        ];
    });
}

function cyan(str) {
    return `\x1b[1m\x1b[36m${str}\x1b[39m\x1b[22m`;
}

function getSize(data) {
    return `${(data.length / 1024).toFixed(2)}kb`;
}