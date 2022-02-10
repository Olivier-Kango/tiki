import { defineAbility } from '@casl/ability';

export default (user) => defineAbility((can) => {
    can('read', 'Card');

    if (user && user.user === 'admin') {
        can('update', 'Card');
        can('create', 'Card');
    }
});
