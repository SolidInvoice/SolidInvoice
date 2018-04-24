export default {
    state: {
        form: {}
    },
    getters: {},
    mutations: {
        setFormField(state, args) {
            console.log(state.form);
            state.form[args.form] = args.value;
        }
    },
    actions: {
        formInput({commit}, args) {
            commit('setFormField', args)
        }
    },
    modules: {}
}