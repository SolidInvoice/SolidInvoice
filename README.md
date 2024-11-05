<h1 align="center">SolidInvoice</h1>

![Screenshot 2024-02-29 at 13 40 04](https://github.com/SolidInvoice/SolidInvoice/assets/144858/6f45c11d-d73e-423e-be4a-30cdf2fe819d)

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

## Screenshots

![Screenshot 2024-02-29 at 13 52 44](https://github.com/SolidInvoice/SolidInvoice/assets/144858/efdb4dc1-5b5f-4fa6-a90e-bd6d1bac186a)

<hr />

![Screenshot 2024-02-29 at 13 53 22](https://github.com/SolidInvoice/SolidInvoice/assets/144858/b89c1239-2455-48ef-9ee0-47b78cf69483)

<hr />

![Screenshot 2024-02-29 at 13 54 36](https://github.com/SolidInvoice/SolidInvoice/assets/144858/a04e2aad-ee98-4615-9096-e16d647534f5)

<hr />

![Screenshot 2024-02-29 at 13 56 14](https://github.com/SolidInvoice/SolidInvoice/assets/144858/bbd16da6-61ed-4b20-8a12-f78b1a20c39f)

<hr />

![Screenshot 2024-02-29 at 13 56 52](https://github.com/SolidInvoice/SolidInvoice/assets/144858/fcc7e26e-6c58-4706-9891-1b00df371873)


## System Requirements

SolidInvoice requires PHP version 8.2 or later for optimal performance. It is recommended to use the latest available version of PHP.

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

5. Install the necessary Node packages and compile all assets with [Bun](https://bun.sh):
```bash
bun install
bun run dev
```

At this point, SolidInvoice should be fully functional.

For production environments, follow these additional steps:

1. Build the project and optimize it for production:
```bash
bun run build
```

2. Deploy the optimized version of SolidInvoice.

## Contribution Guidelines

For information on contributing to the project, please refer to the [CONTRIBUTING](CONTRIBUTING.md) file.

## License

SolidInvoice is licensed under the MIT license, an open-source software license. For detailed information, please consult the [LICENSE](LICENSE) file.

## Sponsorship

We extend our gratitude to all the sponsors who support this project!

<a class="btn" aria-label="Sponsor @SolidInvoice" href="https://github.com/sponsors/SolidInvoice?o=esc">
    <span>Sponsor @SolidInvoice</span>
</a>

### Thank you to the following sponsors:

* JetBrains (PHPStorm License)
* Docker (Docker Hub Subscription)
* Sentry (Sponsored Business plan)

[1]: http://getcomposer.org
