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

        // Return "https" URLs by setting secure: true
        cloudinary.config({ secure: true });

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

        const uploaded = await Promise.all(uploads);

        return uploaded.map((image) => ({
            url: image.url,
            name: image.original_filename,
        }));
    } catch (error) {
        core.warning(`Failed to upload screenshots to Cloudinary: ${error}`);
        return [];
    }
};

const capBody = (body) => {
    if (body.length <= MAX_BODY_LENGTH) {
        return body;
    }

    return `${body.slice(0, MAX_BODY_LENGTH)}\n\n_Report truncated at ${MAX_BODY_LENGTH} characters._`;
};

module.exports = async ({ github, context, core }) => {
    try {
        const rootDir = path.resolve(path.join(__dirname, '..'));
        const { JOB_NAME } = process.env;

        let body = buildHeader({ jobName: JOB_NAME, sha: context.sha, ref: context.ref });

        const logTail = readLogTail(rootDir);

        if (logTail) {
            body += `\n### Log File${logTail.trimmed ? ` (last ${LOG_TAIL_LINES} lines)` : ''}\n\`\`\`\n${logTail.content}\n\`\`\`\n`;
        } else {
            body += '\n_No `var/log/test.log` was produced._\n';
        }

        const hasIssueNumber = Boolean(context.issue && context.issue.number);

        if (hasIssueNumber) {
            const screenshots = await uploadScreenshots(rootDir, context, core);

            if (screenshots.length > 0) {
                body += '\n### Screenshots\n';
                screenshots.forEach((screenshot) => {
                    body += `**${screenshot.name}**\n![screenshot-${screenshot.name}](${screenshot.url})\n`;
                });
            }
        } else {
            core.info('No issue/PR number on this event (likely a push build); skipping screenshot upload and the comment, writing the summary only.');
        }

        body = capBody(body);

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
