<template>
  <span>
    <Grid
      v-bind="currentGrid"
      :key="currentGrid.name"
    >
      <template #header>
        <div
          class="btn-group btn-group-sm"
          role="group"
        >
          <a
            v-for="grid in grids"
            :key="grid.name"
            href="#"
            class="btn btn-dark"
            :class="{ 'active disabled': grid.name === currentGrid.name }"
            @click="currentGrid = grid"
          >
            <i :class="`fas fa-${grid.icon}`" />
            {{ grid.title }}
          </a>
        </div>
      </template>
    </Grid>
  </span>
</template>

<script setup>
    import Grid from './Grid.vue';
    import { ref } from 'vue';

    const props = defineProps({
        grids: {
            type: Object,
            required: true,
        },
    });

    const currentGrid = ref(Object.values(props.grids)[0]);

</script>
