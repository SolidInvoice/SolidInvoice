const cloudinary = require('cloudinary').v2;
const fs = require('fs');
const path = require('path');

module.exports = async ({ github, context }) => {
    const { JOB_NAME } = process.env;
    const rootDir = path.resolve(path.join(__dirname, '..'));
    let commentBody = '### Functional Test Failure 🙀!';

    commentBody += `
| Job Name | SHA | REF |
|----------|-----|-----|
| ${JOB_NAME} | ${context.sha} | ${context.ref} |

`;

    // Return "https" URLs by setting secure: true
    cloudinary.config({
        secure: true,
    });

    const uploadImages = async () => {
        let promiseArray = [];

        let images = fs.readdirSync(`${rootDir}/var/error-screenshots/`);

        images.forEach((element) => {
            console.log(`Uploading ${element} to cloudinary..`);

            let uplaodedImagePromise = cloudinary.uploader.upload(
                `${rootDir}/var/error-screenshots/${element}`,
                {
                    tags: `ci,github-actions,e2e,screenshot,${context.ref}`,
                    folder: `solidinvoice/ci/errors/${context.issue.number}/${context.sha}`,
                    sign_url: true,
                    use_filename: true,
                    unique_filename: false,
                    overwrite: true,
                }
            );
            promiseArray.push(uplaodedImagePromise);
        });

        const urlList = await Promise.all(promiseArray);

        return urlList.map((element) => ({
            url: element.url,
            name: element.original_filename,
        }));
    };

    const urlList = await uploadImages();

    urlList.forEach((element) => {
        commentBody += `**${element.name}**\n![screenshot-${element.name}](${element.url}) \n`;
    });

    if (fs.existsSync(`${rootDir}/var/log/test.log`)) {
        const logFileContent = fs.readFileSync(`${rootDir}/var/log/test.log`);

        commentBody += `\n ### Log File\n \`\`\`${logFileContent}\`\`\``;
    }

    github.rest.issues.createComment({
        issue_number: context.issue.number,
        owner: context.repo.owner,
        repo: context.repo.repo,
        body: commentBody,
    });
};

