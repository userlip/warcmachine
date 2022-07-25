const {
    WARCGzParser
} = require('node-warc');
const fs = require('fs');

var data = [];
var warc_file = process.argv[2];

exports.regex_patterns = {
    access_key_id: /(\'A|"A)(SIA|KIA|IDA|ROA)[JI][A-Z0-9]{14}[AQ][\'"]/g,
    user_pool_id: /[\'"](us|ap|ca|eu)((-gov)|(-iso(b?)))?-[a-z]+-\d{1}_[a-zA-Z0-9]{9}[\'"]/g,
    // identity_pool_id: /[\'"](us|ap|ca|eu)-(central|east|west|south|northeast|southeast)-(1|2):[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}[\'"]/g,
    identity_pool_id: /[\'"](us|ap|ca|eu)((-gov)|(-iso(b?)))?-[a-z]+-\d{1}:[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}[\'"]/g,
    hosted_ui: /[\'"]https:\/\/[^ ]+?\/login\?[^ ]*?client_id=[a-z0-9]{26}[^ ]/g,
    cognito_domain: /[\'"]https:\/\/[a-z0-9\-]+\.auth\.(us|ap|ca|eu)((-gov)|(-iso(b?)))?-[a-z]+-\d{1}\.amazoncognito\.com/g,
    assumerolewithwebidentity: /assumeRoleWithWebIdentity\(/g,
    arn: /arn:aws:[a-z0-9-]+:((us|ap|ca|eu)((-gov)|(-iso(b?)))?-[a-z]+-\d{1})?:(\d{12})?:[a-z0-9-]+([\/:][a-zA-Z0-9_+=,.@-]+)?/g,

    google_appid: /[\'"][0-9]{12}-[0-9a-z]{32}\.apps\.googleusercontent\.com[\'"]/g,

    amazon_appid: /[\'"]amzn1\.application-oa2-client\.[0-9a-f]{32}[\'"]/g,
    amazon_authorize: /amazon\.Login\.authorize\(/g,

    // Find s3 buckets
    s3_buckets: /https?:\/\/[^ \.\/]+?\.s3\.amazonaws\.com/g,

    // Find proxies
    safebase64_url: /['"]https?:\/\/[^'"]+[&?/]{1}aHR0c[A-Za-z0-9_-]+[^ ]*?['"]/g,
    base64_url: /['"]https?:\/\/[^'"]+[&?/]{1}aHR0c[A-Za-z0-9+/]+={0,2}[^ ]*?['"]/g,
};

const combined = new RegExp(Object.keys(regex_patterns).map(e => {
    return `(?<${e}>${regex_patterns[e].source})`
}).join("|"), 'g');

const metrics = {
    total_hits: 0,
    regex_hits: {}
};

const parser = new WARCGzParser(warc_file)
parser.on('record', record => {
    1

    // Only process response records with mime-types we care about.
    if (record.warcHeader['WARC-Type'] != "response") {
        return true;
    }

    const matches = record.content.toString().matchAll(combined);

    // matchAll is an iterator with one match per capture group per yield
    // Capture groups with no match are undefined.
    for (const match of matches) {

        metrics.regex_hits.push({
            value: match.groups.email,
            link: record.warcHeader['WARC-Target-URI']
        })

        Object.keys(match.groups).map(e => {
            if (!!!match.groups[e]) {
                return false;
            }

            let value = match.groups[e];
            if (!!custom_functions[e]) {
                value = custom_functions[e](value);

                // (return === false) drops the result
                if (value === false) {
                    return false;
                }
            }

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
    fs.writeFileSync('localResults.json', JSON.stringify(metrics));
});

parser.on('error', error => {
    console.error(error)
});
parser.start()
