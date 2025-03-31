variable "IMAGE_NAME" {
    default = "solidinvoice/solidinvoice"
}

variable "SOLIDINVOICE_VERSION" {
    default = "dev"
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
    }
}

target "linux-6amd64_binary" {
    context = "."
    dockerfile = "docker/Dockerfile.linux-static-build"
    platforms = ["linux/amd64"]
    args = {
        SOLIDINVOICE_VERSION = "${SOLIDINVOICE_VERSION}"
    }
}
