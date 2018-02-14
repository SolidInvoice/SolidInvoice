<template>
    <span>
        <div class="form-group input-group" v-for="(item, index) in items" :key="item">
            <div class="input-group-btn input-group-select">
                <component :is="setPrototype(index, type)" v-bind="componentData"></component>
            </div>
            <component :is="setPrototype(index, value)" v-bind="componentData"></component>

            <span class="input-group-btn">
                <button type="button" class="btn btn-danger btn-remove" @click="removeRow(index)" v-if="index + 1 < items.length">-</button>
                <button type="button" class="btn btn-success btn-add" @click="addRow()" v-if="index + 1 === items.length">+</button>
            </span>
            <div class="form-group has-error" v-html="formErrors">
            </div>
        </div>
    </span>
</template>

<script>
    import Vue from 'vue';

    export default {
        props: {
            value: {
                required: false
            },
            type: {
                required: true
            },
            formErrors: {
                required: false
            },
            props: {
                required: false
            }
        },
        methods: {
            addRow() {
                this.items.push(this.items.length + 1);
            },
            removeRow(index) {
                this.items.splice(index, 1);
            },
            setPrototype(index, text) {
                let component = Vue.compile(text.replace(/__contact_details_prototype__/gi, index));

                component.props = {
                    props: {
                        required: false
                    }
                };

                return component;
            }
        },
        data() {
            return {
                items: [0],
                componentData: {
                    props: this.props
                }
            }
        }
    }
</script>

<style scoped lang="less">
    .btn {
        border-radius: 0 !important;
    }
</style>