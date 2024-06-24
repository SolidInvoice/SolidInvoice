<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use SolidInvoice\UserBundle\Action\AcceptInvitation;
use SolidInvoice\UserBundle\Action\ApiIndex;
use SolidInvoice\UserBundle\Action\EditProfile;
use SolidInvoice\UserBundle\Action\ForgotPassword\Check;
use SolidInvoice\UserBundle\Action\ForgotPassword\Request;
use SolidInvoice\UserBundle\Action\ForgotPassword\Reset;
use SolidInvoice\UserBundle\Action\ForgotPassword\Send;
use SolidInvoice\UserBundle\Action\InviteUser;
use SolidInvoice\UserBundle\Action\Notifications;
use SolidInvoice\UserBundle\Action\Profile;
use SolidInvoice\UserBundle\Action\Register;
use SolidInvoice\UserBundle\Action\ResendUserInvite;
use SolidInvoice\UserBundle\Action\Security\ChangePassword;
use SolidInvoice\UserBundle\Action\Security\Login;
use SolidInvoice\UserBundle\Action\Users;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_api_keys_index', '/profile/api')
        ->controller(ApiIndex::class)
        ->options(['expose' => true]);

    $routingConfigurator
        ->import('@SolidInvoiceUserBundle/Resources/config/routing/ajax.php')
        ->prefix('/profile/api/xhr')
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_users_list', '/users')
        ->controller(Users::class);

    $routingConfigurator
        ->add('_user_invite', '/users/invite')
        ->controller(InviteUser::class);

    $routingConfigurator
        ->add('_user_resend_invite', '/users/invite/{id}/resend')
        ->controller(ResendUserInvite::class)
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_user_accept_invite', '/invite/accept/{id}')
        ->controller(AcceptInvitation::class);

    $routingConfigurator
        ->add('_login', '/login')
        ->controller(Login::class);

    $routingConfigurator
        ->add('_register', '/register')
        ->controller(Register::class);

    $routingConfigurator->add('_logout', '/logout');

    $routingConfigurator->add('_login_check', '/login-check');

    $routingConfigurator
        ->add('_user_forgot_password', '/forgot-password')
        ->controller(Request::class);

    $routingConfigurator
        ->add('_user_forgot_password_send_emal', '/forgot-password/send')
        ->controller(Send::class);

    $routingConfigurator
        ->add('_user_forgot_password_check_email', '/forgot-password/check')
        ->controller(Check::class);

    $routingConfigurator
        ->add('_user_password_reset', '/forgot-password/reset/{token}')
        ->controller(Reset::class);

    $routingConfigurator
        ->add('_profile', '/profile')
        ->controller(Profile::class);

    $routingConfigurator
        ->add('_edit_profile', '/profile/edit')
        ->controller(EditProfile::class);

    $routingConfigurator
        ->add('_change_password', '/profile/change-password')
        ->controller(ChangePassword::class);

    $routingConfigurator
        ->add('_profile_notifications', '/profile/notifications')
        ->controller(Notifications::class);
};
