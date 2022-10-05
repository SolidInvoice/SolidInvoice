const cloudinary = require('cloudinary').v2;
const fs = require('fs');
const path = require('path');

module.exports = async ({ github, context }) => {
    const cwd = path.resolve(__dirname);
    let commentBody = '';

    // Return "https" URLs by setting secure: true
    cloudinary.config({
        secure: true,
    });

    const uploadImages = async () => {
        let promiseArray = [];

        let images = fs.readdirSync(`${cwd}/var/error-screenshots/`);

        images.forEach((element) => {
            console.log('uploading image to cloudinary..');

            let uplaodedImagePromise = cloudinary.uploader.upload(
                `${cwd}/var/error-screenshots/${element}`,
                {
                    tags: 'ci,github-actions,e2e,screenshot',
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
        commentBody =
            commentBody +
            `### ${element.name} \n <br /> ![screenshot-${element.name}](${element.url}) \n`;
    });

    github.rest.issues.createComment({
        issue_number: context.issue.number,
        owner: context.repo.owner,
        repo: context.repo.repo,
        body: `Functional Test Failure 🙀!

        ${commentBody}
        `,
    });
};

