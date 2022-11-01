<template>
  <b-dropdown :text="dropDownLabel">
    <b-dropdown-item-button v-for="item in options" :key="item.text" @click="setItem(item)">
      {{ item.text }}
    </b-dropdown-item-button>
  </b-dropdown>
</template>

<script lang="ts" setup>
  import { ref, PropType } from 'vue'
  import { BDropdown, BDropdownItemButton } from 'bootstrap-vue';

  type OptionType = {
    text: string;
    value: string|number;
  };

  const props = defineProps({
    options: {
      type: Array as PropType<OptionType[]>,
      required: true
    },
    label: {
      type: String,
      required: false,
      default: 'Please Select',
    },
    formField: {
      type: String,
      required: false,
      default: '',
    },
  });

  const emit = defineEmits<{
    (e: 'change', item: OptionType): void,
    (e: 'bv::dropdown::hide', item: null): void,
  }>();

  const dropDownLabel = ref(props.label);

  const setItem = (item: OptionType) => {
    dropDownLabel.value = item.text;

    if (props.formField !== '') {
      console.log(props.formField);

      (document.querySelector(`input[name="${props.formField}"]`) as HTMLInputElement).value = String(item.value);
    }

    emit('change', item);
  };
</script>
