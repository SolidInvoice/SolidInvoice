<template>
    <div class="text-xs-center">
        <v-menu offset-y>
            <v-btn :small="small" color="primary" slot="activator">
                {{ selected.name || label }}
            </v-btn>
            <v-list>
                <v-list-tile v-for="item in items" :key="item.title" @click="setSelected(item)" v-if="item !== selected">
                    <v-list-tile-title>{{ item.name }}</v-list-tile-title>
                </v-list-tile>
            </v-list>
        </v-menu>
    </div>
</template>

<script>
    export default {
        props: {
            items: {
                type: Array,
                required: true
            },
            label: {
                type: String,
                required: false,
                default: 'Choose'
            },
            model: {
                type: String,
                required: false
            },
            small: {
                type: Boolean,
                default: false
            }
        },
        data() {
            return {
                selected: {
                    name: null
                }
            };
        },
        methods: {
            setSelected(item) {
                this.selected = item;
                this.$emit('input', item.id);

                if (this.model) {
                    document.querySelector(this.model).value = item.id;
                }
            }
        }
    }
</script>