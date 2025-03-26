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

target "linux-arm64_binary" {
    context = "."
    dockerfile = "docker/Dockerfile.linux-static-build"
    platforms = ["linux/amd64"]
    args = {
        SOLIDINVOICE_VERSION = "${VERSION}"
    }
}

target "linux_arm64_binary" {
    context = "."
    dockerfile = "docker/Dockerfile.linux-static-build"
    platforms = ["linux/amd64"]
    args = {
        SOLIDINVOICE_VERSION = "${VERSION}"
    }
}
