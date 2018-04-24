<template>
    <v-card>
        <v-card-title primary-title class="mb-0 pb-0">
            <h3 class="headline">{{ title }}</h3>
        </v-card-title>

        <v-card-text class="mt-0">
            <slot name="body"/>
        </v-card-text>

        <v-card-actions>
            <slot name="footer"/>
        </v-card-actions>
    </v-card>
</template>

<script>
    export default {
        props: {
            title: {
                type: String,
                required: true,
            }
        },
    }
</script>