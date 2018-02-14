<template>
    <span>
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="concept">
                        {{ selected.name || label }}
                    </span>
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu" role="menu">
            <li v-for="item in items" v-if="item !== selected">
                <a href="#" :data-value="item.id" :data-type="item.name" @click.prevent="selected = item">
                    {{ item.name }}
                </a>
            </li>
        </ul>
    </span>
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
            }
        },
        data() {
            return {
                selected: {
                    name: null
                }
            };
        }
    }
</script>