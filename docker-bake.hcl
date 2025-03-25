variable "IMAGE_NAME" {
    default = "solidinvoice/solidinvoice"
}

variable "VERSION" {
    default = "dev"
}

group "default" {
    targets = [
        "linux_amd64",
        "linux_arm64",
    ]
}

target "linux_amd64" {
    context = "."
    dockerfile = "docker/Dockerfile.build"
    platforms = ["linux/amd64"]
    tags = ["${IMAGE_NAME}:${VERSION}-linux_amd64"]
    args = {
        BINARY = "frankenphp/dist/solidinvoice-mac-arm64"
    }
}

target "linux_arm64" {
    context = "."
    dockerfile = "docker/Dockerfile.build"
    platforms = ["linux/arm64"]
    tags = ["${IMAGE_NAME}:${VERSION}-linux_arm64"]
    args = {
        BINARY = "frankenphp/dist/solidinvoice-mac-arm64"
    }
}
