<template>
    <v-layout row>
        <v-flex xs10>
            <slot :item="{$bus, errors}"></slot>
        </v-flex>
        <v-flex xs2 d-flex>
            <v-tooltip top>
                <v-btn color="primary" flat outline @click="validate" slot="activator">
                    <span v-if="!validating">
                        {{ title }}
                    </span>
                    <i class="fa fa-spin fa-refresh" v-if="validating"></i>
                </v-btn>
                <span>EU Countries Only</span>
            </v-tooltip>
        </v-flex>
    </v-layout>
</template>

<script>
    export default {
        inject: ['http'],
        props: {
            title: {
                type: String,
                required: false,
                default: 'Validate'
            }
        },
        data() {
            return {
                validating: false,
                value: null,
                errors: []
            };
        },
        computed: {
            $bus() {
                return this;
            }
        },
        mounted() {
            this.$on('input', (value) => {
                this.value = value;
            });
        },
        methods: {
            validate() {
                this.validating = true;

                this.errors = [];

                this.http.post('_tax_number_validate', {}, {'vat_number': this.value})
                    .then((result) => {
                        if (false === result.data.valid) {
                            this.errors.push('Vat number is invalid');
                        }
                    })
                    .catch((error) => {
                        this.errors.push('Vat number is invalid');
                    })
                    .finally(() => {
                        this.validating = false;
                    });
            }
        }
    }
</script>

<style scoped lang="less">
    .btn {
        margin: 13px 0 0 -8px !important;
        padding: 0 !important;
    }
</style>