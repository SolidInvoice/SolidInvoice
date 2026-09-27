const fs = require('fs');
const path = require('path');

const MAX_BODY_LENGTH = 60000;
const LOG_TAIL_LINES = 400;

const isCloudinaryConfigured = (url) => {
    if (!url) {
        return false;
    }

    // Secrets that interpolate empty on a fork PR leave this exact shape.
    return !/^cloudinary:\/\/:@?$/.test(url);
};

const buildHeader = ({ jobName, sha, ref }) => `### Functional Test Failure 🙀!
| Job Name | SHA | REF |
|----------|-----|-----|
| ${jobName} | ${sha} | ${ref} |

`;

// A fence must be at least as long as the longest run of backticks already in the
// content, or a log line like "```js" or "`````" closes it early and the rest of the
// log renders as Markdown instead of log text.
const fenceFor = (content) => {
    const runs = content.match(/`+/g) || [];
    const longest = runs.reduce((max, run) => Math.max(max, run.length), 0);
    return '`'.repeat(Math.max(3, longest + 1));
};

const readLogTail = (rootDir) => {
    const logPath = path.join(rootDir, 'var/log/test.log');

    if (!fs.existsSync(logPath)) {
        return null;
    }

    const lines = fs.readFileSync(logPath, 'utf8').split('\n');
    const tail = lines.slice(-LOG_TAIL_LINES);

    return {
        content: tail.join('\n'),
        trimmed: lines.length > tail.length,
    };
};

const uploadScreenshots = async (rootDir, context, core) => {
    const screenshotsDir = path.join(rootDir, 'var/browser/screenshots');

    if (!fs.existsSync(screenshotsDir)) {
        return [];
    }

    if (!isCloudinaryConfigured(process.env.CLOUDINARY_URL)) {
        core.info('CLOUDINARY_URL is not configured (expected on a fork PR); skipping screenshot upload.');
        return [];
    }

    try {
        const cloudinary = require('cloudinary').v2;

        const images = fs.readdirSync(screenshotsDir);

        const uploads = images.map((image) => cloudinary.uploader.upload(
            path.join(screenshotsDir, image),
            {
                tags: `ci,github-actions,e2e,screenshot,${context.ref}`,
                folder: `solidinvoice/ci/errors/${context.issue.number}/${context.sha}`,
                sign_url: true,
                use_filename: true,
                unique_filename: false,
                overwrite: true,
            }
        ));

        // allSettled: one bad file (wrong type, transient network error) must not
        // drop every other screenshot from the report.
        const settled = await Promise.allSettled(uploads);

        settled
            .filter((result) => result.status === 'rejected')
            .forEach((result) => core.warning(`Failed to upload a screenshot to Cloudinary: ${result.reason}`));

        return settled
            .filter((result) => result.status === 'fulfilled')
            .map((result) => ({
                // .secure_url is the https field on the upload response; .url is always http.
                url: result.value.secure_url,
                name: result.value.original_filename,
            }));
    } catch (error) {
        core.warning(`Failed to upload screenshots to Cloudinary: ${error}`);
        return [];
    }
};

// Screenshot links have no second home - the log tail is also in the job summary, the
// shell fallback step and the raw step log, but the Cloudinary URLs exist only here. So
// the screenshot section is reserved first and the log is what gets truncated to fit.
const buildLogSection = (logTail, budget) => {
    if (!logTail) {
        return '\n_No `var/log/test.log` was produced._\n';
    }

    const heading = `\n### Log File${logTail.trimmed ? ` (last ${LOG_TAIL_LINES} lines)` : ''}\n`;
    const truncationNote = '\n\n_Log truncated to fit the report size limit._';
    const fence = fenceFor(logTail.content);
    const render = (content, isTruncated) => `${heading}${fence}\n${content}\n${fence}${isTruncated ? truncationNote : ''}\n`;

    if (render(logTail.content, false).length <= budget) {
        return render(logTail.content, false);
    }

    const overhead = render('', true).length;
    const content = logTail.content.slice(0, Math.max(0, budget - overhead));

    return render(content, true);
};

module.exports = async ({ github, context, core }) => {
    try {
        const rootDir = path.resolve(__dirname, '..');
        const { JOB_NAME } = process.env;

        const header = buildHeader({ jobName: JOB_NAME, sha: context.sha, ref: context.ref });
        const hasIssueNumber = Boolean(context.issue && context.issue.number);

        let screenshotSection = '';

        if (hasIssueNumber) {
            const screenshots = await uploadScreenshots(rootDir, context, core);

            if (screenshots.length > 0) {
                screenshotSection = '\n### Screenshots\n';
                screenshotSection += screenshots
                    .map((screenshot) => `**${screenshot.name}**\n![screenshot-${screenshot.name}](${screenshot.url})\n`)
                    .join('');
            }
        } else {
            core.info('No issue/PR number on this event (likely a push build); skipping screenshot upload and the comment, writing the summary only.');
        }

        const logTail = readLogTail(rootDir);
        const logBudget = MAX_BODY_LENGTH - header.length - screenshotSection.length;
        const logSection = buildLogSection(logTail, logBudget);

        const body = header + logSection + screenshotSection;

        if (hasIssueNumber) {
            try {
                await github.rest.issues.createComment({
                    issue_number: context.issue.number,
                    owner: context.repo.owner,
                    repo: context.repo.repo,
                    body,
                });
            } catch (error) {
                core.warning(`Failed to post the failure comment: ${error}`);
            }
        }

        core.summary.addRaw(body);
        await core.summary.write();
    } catch (error) {
        core.warning(`e2e-failure reporter failed: ${error}`);
    }
};
