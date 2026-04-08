variable "IMAGE_NAME" {
    default = "solidinvoice/solidinvoice"
}

variable "SOLIDINVOICE_VERSION" {
    default = "2.4.x"
}

variable "PHP_VERSION" {
    default = "8.4"
}

variable "GO_VERSION" {
    default = "1.25"
}

variable "LATEST" {
    default = false
}

variable "NIGHTLY" {
    default = false
}

variable "PREVIEW" {
    default = false
}

variable "RELEASE" {
    default = 0
}

variable "NO_COMPRESS" {
    default = 1  // Binaries are small enough for now, no need to compress them
}

variable "CI" {
    # CI flag coming from the environment or --set; empty by default
    default = ""
}

variable "SPC_OPT_BUILD_ARGS" {
    default = ""
}

# cleanTag ensures that the tag is a valid Docker tag
# see https://github.com/distribution/distribution/blob/v2.8.2/reference/regexp.go#L37
function "clean_tag" {
    params = [tag]
    result = substr(regex_replace(regex_replace(tag, "[^\\w.-]", "-"), "^([^\\w])", "r$0"), 0, 127)
}

# semver adds semver-compliant tag if a semver version number is passed, or returns the revision itself
# see https://semver.org/#is-there-a-suggested-regular-expression-regex-to-check-a-semver-string
function "semver" {
    params = [rev]
    result = __semver(_semver(regexall("^v?(?P<major>0|[1-9]\\d*)\\.(?P<minor>0|[1-9]\\d*)\\.(?P<patch>0|[1-9]\\d*)(?:-(?P<prerelease>(?:0|[1-9]\\d*|\\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\\.(?:0|[1-9]\\d*|\\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\\.[0-9a-zA-Z-]+)*))?$", rev)))
}

function "_semver" {
    params = [matches]
    result = length(matches) == 0 ? {} : matches[0]
}

function "__semver" {
    params = [v]
    result = v == {} ? [clean_tag(SOLIDINVOICE_VERSION)] : v.prerelease == null ? [v.major, "${v.major}.${v.minor}", "${v.major}.${v.minor}.${v.patch}"] : ["${v.major}.${v.minor}.${v.patch}-${v.prerelease}"]
}

group "default" {
    targets = [
        "build-static",
    ]
}

target "build-static" {
    contexts = {
        golang-base = "docker-image://golang:${GO_VERSION}-alpine"
    }
    context = "."
    dockerfile = "docker/linux-static-build.Dockerfile"
    platforms = [
        "linux/amd64",
        "linux/arm64",
        // @TODO: Add support for more architectures
        //"linux/386",
        //"linux/arm/v6",
        //"linux/arm/v7",
    ]
    tags = compact(distinct(flatten([
            LATEST ? "${IMAGE_NAME}:latest" : "",
            NIGHTLY ? "${IMAGE_NAME}:nightly" : "",
            PREVIEW ? flatten(["${IMAGE_NAME}:next", "${IMAGE_NAME}:3.0-dev"]) : flatten([]),
            SOLIDINVOICE_VERSION == "2.4.x" ? [] : [for v in semver(SOLIDINVOICE_VERSION) : "${IMAGE_NAME}:${v}"]
    ])))
    args = {
        SOLIDINVOICE_VERSION = "${SOLIDINVOICE_VERSION}"
        PHP_VERSION = "${PHP_VERSION}"
        # RELEASE removed — Linux binary upload now handled by the workflow
        NO_COMPRESS = "${NO_COMPRESS}"
        LATEST = "${LATEST}"
        NIGHTLY = "${NIGHTLY}"
        PREVIEW = "${PREVIEW}"
        CI = CI
        SPC_OPT_BUILD_ARGS = SPC_OPT_BUILD_ARGS
    }
    secret = ["id=github-token,env=GITHUB_TOKEN"]
}

target "package" {
    context = "docker"
    dockerfile = "package.Dockerfile"
    platforms = [
        "linux/amd64",
        "linux/arm64",
    ]
    tags = compact(distinct(flatten([
            LATEST ? "${IMAGE_NAME}:latest" : "",
            NIGHTLY ? "${IMAGE_NAME}:nightly" : "",
            PREVIEW ? flatten(["${IMAGE_NAME}:next", "${IMAGE_NAME}:3.0-dev"]) : flatten([]),
            SOLIDINVOICE_VERSION == "2.4.x" ? [] : [for v in semver(SOLIDINVOICE_VERSION) : "${IMAGE_NAME}:${v}"]
    ])))
    args = {
        SOLIDINVOICE_VERSION = "${SOLIDINVOICE_VERSION}"
    }
}
