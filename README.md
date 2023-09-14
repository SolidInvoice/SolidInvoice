<p align="center">
    <a href="https://github.com/SolidInvoice/SolidInvoice" target="_blank">
        <img src="https://avatars.githubusercontent.com/u/25333451?s=200&v=4" height="100px">
    </a>
    <h1 align="center">SolidInvoice</h1>
    <br>
</p>

SolidInvoice is a sophisticated open-source invoicing application designed to assist small businesses and freelancers in efficiently managing their daily billing operations. With its comprehensive range of features, this elegant online platform ensures that you receive timely payments.

## Key Features

- Robust clients and contacts management system
- Creation and management of quotes
- Generation and oversight of invoices
- Seamless online payment acceptance
- Effective handling of taxes and discounts
- RESTful API for integration with other systems
- Receive notifications through various channels, including text messages, emails, or HipChat
- Future enhancements planned

## System Requirements

SolidInvoice requires PHP version 8.1 or later for optimal performance. It is recommended to use the latest available version of PHP.

## Installation Options

### Docker

Getting started with SolidInvoice is quick and simple using Docker. The Docker image can be found at [https://hub.docker.com/r/solidinvoice/solidinvoice/](https://hub.docker.com/r/solidinvoice/solidinvoice/), along with instructions on how to begin.

### Archived Package

To install SolidInvoice from an archived package, download the latest release in either the `zip` or `tar.gz` format from [https://github.com/SolidInvoice/SolidInvoice/releases](https://github.com/SolidInvoice/SolidInvoice/releases). Extract the contents of the archive into the appropriate directory within your web server.

### Installation for Developers

If you prefer to install SolidInvoice from the source code, follow these steps:

1. Clone the repository by executing the following command:
```bash
git clone https://github.com/SolidInvoice/SolidInvoice.git
```
Ensure that you choose a destination path accessible from your web server.

2. Navigate to the cloned repository:
```bash
cd SolidInvoice
```

3. Obtain Composer, a dependency management tool, by running:
```bash
curl -s http://getcomposer.org/installer | php
```

4. Once Composer has finished downloading, install the required dependencies:
```bash
php composer.phar install
```

5. Install the necessary Node packages and compile all assets:
```bash
yarn install
yarn dev
```

At this point, SolidInvoice should be fully functional.

For production environments, follow these additional steps:

1. Build the project and optimize it for production:
```bash
yarn build
```

2. Deploy the optimized version of SolidInvoice.

## Contribution Guidelines

For information on contributing to the project, please refer to the [CONTRIBUTING](CONTRIBUTING.md) file.

## License

SolidInvoice is licensed under the MIT license, an open-source software license. For detailed information, please consult the [LICENSE](LICENSE) file.

## Sponsorship

We extend our gratitude to all the sponsors who support this project!

<a class="btn" aria-label="Sponsor @SolidInvoice" href="/sponsors/SolidInvoice?o=esc">
    <span>Sponsor @SolidInvoice</span>
</a>

### Thank you to the following sponsors:

* JetBrains (PHPStorm License)
* Docker (Docker Hub Subscription)
* Sentry (Sponsored Business plan)

[1]: http://getcomposer.org
