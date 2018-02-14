<template>
    <div>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ title }}</h3>
            </div>

            <div class="box-body">
                <slot name="body"/>
            </div>

            <div class="box-footer">
                <slot name="footer"/>
                <portal-target :name="target" slim multiple>
                </portal-target>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            title: {
                type: String,
                required: true,
            },
            portalTarget: {
                type: String,
            }
        },
        data() {
            return {
                target: this.portalTarget || this.title
            }
        }
    }
</script>