define(['core/module', 'core/view', 'core/modal', 'template'], (Module, ItemView, Modal, Template) => {
    console.log(Template);
    return Module.extend({
        regions: {
            'generatePassword': '#generate-password',
        },
        initialize() {
            const view = ItemView.extend({
                tagName: 'span',
                'template': Template.user.generate_password,
                'ui': {
                    'generatePassword': '#generate'
                },
                'events': {
                    'click @ui.generatePassword': 'generate'
                },
                generate() {
                    let module = this,
                        modal = Modal.extend({
                            'template': '<pre>' + this.generateRandomString(8) + '</pre>',
                            'events': {
                                'click @ui.save': 'regenerate'
                            },
                            regenerate() {
                                this.template = '<pre>' + module.generateRandomString(8) + '</pre>';
                                this.render();
                            },
                            'modal': {
                                'buttons': {
                                    'Close': {
                                        'close': true
                                    },
                                    'Regenerate': {
                                        'class': 'success',
                                        'save': true
                                    }
                                }
                            }
                        });

                    (new modal).render();
                },
                generateRandomString(length) {
                    return Math.random().toString(36).slice(-length);
                }
            });

            this.app.showChildView('generatePassword', new view);
        }
    })
});