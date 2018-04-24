<script>
    import {isNumber, isObject, isUndefined, uniqueId} from 'lodash';

    export default {
        props: {
            allowDelete: {
                type: Boolean,
                required: false,
                default: true
            },
            allowAdd: {
                type: Boolean,
                required: false,
                default: true
            },
            minItems: {
                type: Number,
                required: false,
                default: 1
            }
        },
        data() {
            return {
                items: [],
                index: 0
            }
        },
        computed: {
            currentItems() {
                return this.$slots.items || [];
            }
        },
        methods: {
            addRow() {
                this.items.push(++this.index);
            },
            removeRow(index) {
                this.items.splice(index, 1);
            }
        },
        mounted: function() {
            if (0 === this.currentItems.length) {
                this.items.push(0);
            } else {
                for (let item of this.currentItems) {
                    this.items.push({item, key: uniqueId()});
                }

                this.index = this.items.length;
            }
        },
        created: function() {
            const vm = this;
            this.$on('addRow', this.addRow);
            this.$on('removeRow', function(index) {
                if (isUndefined(index) || !isNumber(index)) {
                    throw new Error('Index must be set and a valid number when calling "deleteRow"');
                }

                vm.removeRow(index);
            });
        },
        render: function(h) {
            let nodes = [],
                $bus = this;

            const allowDelete = this.items.length > this.minItems && this.allowDelete && this.$scopedSlots.deleteButton;

            for (let index = 0, length = this.items.length; index < length; ++index) {
                let children = [],
                    item = this.items[index],
                    key;

                if (isObject(item)) {
                    children.push(item.item);
                    key = item.key;
                } else {
                    children.push(this.$scopedSlots.prototype({index, length, $bus}));
                    key = item;
                }

                if (allowDelete) {
                    children.push(h('span', {on: {click: () => this.removeRow(index)}}, this.$scopedSlots.deleteButton({index, length, $bus})));
                }

                nodes.push(h('span', {key: key}, children));
            }

            if (this.allowAdd) {
                let addBtn;

                if (this.$slots.addButton) {
                    addBtn = this.$slots.addButton;
                } else if (this.$scopedSlots.addButton) {
                    addBtn = this.$scopedSlots.addButton({length, $bus});
                }

                if (addBtn) {
                    nodes.push(h('span', {on: {click: this.addRow}}, addBtn));
                }
            }

            return h('span', nodes);
        }
    }
</script>