define(['core/module', 'core/view', 'core/modal', 'template'], (Module, ItemView, Modal, Template) => {
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
                            'template': '<pre>' + this.generateRandomString(12) + '</pre>',
                            'events': {
                                'click @ui.save': 'regenerate'
                            },
                            regenerate() {
                                this.template = '<pre>' + module.generateRandomString(12) + '</pre>';
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
                    const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
                    let pass = '';
                    for (let x = 0; x < length; x++) {
                        let i = Math.floor(Math.random() * 62);
                        pass += chars.charAt(i);
                    }

                    return pass;
                }
            });

            this.app.showChildView('generatePassword', new view);
        }
    })
});