<template>
  <div>
    <v-select
      v-model="val"
      :options="options"
      :multiple="multiple"
      :disabled="disabled"
      :placeholder="placeholder"
      label="label"
      value="value"
    />
    <input
      type="hidden"
      :name="formName"
      :value="val?.value"
    />
  </div>
</template>

<script lang="ts" setup>
    import { ref, PropType } from 'vue';
    import VSelect from 'vue-select';

    type OptionType = {
        label: string;
        value: string|number;
    };

    const props = defineProps({
        options: {
            type: Array as PropType<OptionType[]>,
            required: true
        },
        multiple: {
            type: Boolean as PropType<boolean>,
            required: false,
            default: false
        },
        disabled: {
            type: Boolean as PropType<boolean>,
            required: false,
            default: false
        },
        placeholder: {
            type: String as PropType<string|null>,
            required: false,
            default: null,
        },
        label: {
            type: String as PropType<string|null>,
            required: false,
            default: null,
        },
        formName: {
            type: String as PropType<string|null>,
            required: false,
            default: null,
        },
        value: {
            type: Object as PropType<OptionType>,
            required: false,
            default: () => ({value: null}),
        },
    });

    const val = ref<OptionType>(props.value);
</script>
