variable "IMAGE_NAME" {
    default = "solidinvoice/solidinvoice"
}

variable "SOLIDINVOICE_VERSION" {
    default = "dev"
}

variable "PHP_VERSION" {
    default = "8.3"
}

group "default" {
    targets = [
        "build-static",
    ]
}

target "build-static" {
    context = "."
    dockerfile = "docker/Dockerfile.linux-static-build"
    platforms = [
        "linux/amd64",
        "linux/386",
        "linux/arm/v6",
        "linux/arm/v7",
        "linux/arm64",
    ]
    args = {
        SOLIDINVOICE_VERSION = "${SOLIDINVOICE_VERSION}"
        PHP_VERSION = "${PHP_VERSION}"
    }
    secret = ["id=github-token,env=GITHUB_TOKEN"]
}
