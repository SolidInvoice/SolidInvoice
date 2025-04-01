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
        "linux-amd64_binary",
        "linux-arm64_binary",
    ]
}

target "linux-arm64_binary" {
    context = "."
    dockerfile = "docker/Dockerfile.linux-static-build"
    platforms = ["linux/arm64"]
    args = {
        SOLIDINVOICE_VERSION = "${SOLIDINVOICE_VERSION}"
        PHP_VERSION = "${PHP_VERSION}"
    }
    secret = ["id=github-token,env=GITHUB_TOKEN"]
}

target "linux-amd64_binary" {
    context = "."
    dockerfile = "docker/Dockerfile.linux-static-build"
    platforms = ["linux/amd64"]
    args = {
        SOLIDINVOICE_VERSION = "${SOLIDINVOICE_VERSION}"
        PHP_VERSION = "${PHP_VERSION}"
    }
    secret = ["id=github-token,env=GITHUB_TOKEN"]
}
