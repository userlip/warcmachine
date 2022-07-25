const {
    WARCGzParser
} = require('node-warc');
const fs = require('fs');
const crypto = require('crypto');

var data = [];
var warc_file = process.argv[2];

const regex_patterns = {
    email: /[\'\"\s][a-z0-9._-]{0,35}@[a-z0-9._-]+\.[a-z]{2,4}/gmi
};

const combined = new RegExp(Object.keys(regex_patterns).map(e => {
    return `(?<${e}>${regex_patterns[e].source})`
}).join("|"), 'g');

const metrics = {
    total_hits: 0,
    regex_hits: {}
};

const parser = new WARCGzParser(warc_file);

Object.keys(regex_patterns).map((e) => {
    metrics.regex_hits[e] = {};
});

parser.on('record', record => {

    // Only process response records with mime-types we care about.
    if (record.warcHeader['WARC-Type'] != "response") {
        return true;
    }

    // matchAll is an iterator with one match per capture group per yield
    // Capture groups with no match are undefined.
    const matches = record.content.toString().matchAll(combined);
    const domain = record.warcHeader['WARC-Target-URI'].split('/')[2];

    // matchAll is an iterator with one match per capture group per yield
    // Capture groups with no match are undefined.
    for (const match of matches) {
        Object.keys(match.groups).map(e => {
            if (!!!match.groups[e]) {
                return false;
            }

            let value = match.groups[e];

            metrics.total_hits++;
            value = value.trim().replace(/['"]+/g, "");
            const key = hash(value);

            metrics.regex_hits[e][key] ??= {
                value
            };
            metrics.regex_hits[e][key][domain] ??= [];

            let uri = record.warcHeader['WARC-Target-URI'];
            let uris = metrics.regex_hits[e][key][domain];

            if (uris.length < 3 && !uris.includes(uri)) {
                uris.push(uri);
            }

        });
    }
});

parser.on('done', () => {
    console.log(JSON.stringify(metrics));
});

parser.on('error', error => {
    console.error(error)
});
parser.start()

function hash(what) {
    return crypto.createHash("sha1").update(what).digest("hex");
}
