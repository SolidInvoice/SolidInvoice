<template>
    <div class="input-group" v-for="item in items">
        <div class="input-group-btn input-group-select">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="concept">
                        {{ value }}
                        <!--{% for type in contactTypes if type.id == form.type.vars.value %}
                            {{ type.name|title }}
                        {% else %}
                            {{ 'client.contact.details.choose'|trans }}
                        {% endfor %}-->
                    </span>
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <li v-for="type in types">
                    <a href="#" :data-value="type.id" :data-type="type.name">
                        {{ type.name }}
                    </a>
                </li>
            </ul>
            <slot name="contact-type"></slot>
        </div>
        <slot name="contact-value"></slot>

        <span class="input-group-btn">
            <button type="button" class="btn btn-success btn-add" @click="addRow('{{ id }}')">+</button>
        </span>
        <div class="form-group has-error">
            <slot name="form-errors"></slot>
        </div>
    </div>

</template>

<script>
    import ContactInfo from './ContactInfo.vue';

    export default {
        components: [{'contact-info': ContactInfo}],
        props: {
            contactTypes: {
                type: Array,
                required: true
            },
            value: {
                required: false
            }
        },
        data() {
            return {
                items: [this.template]
            }
        },
    }
</script>