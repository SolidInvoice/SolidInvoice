<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return [
    'texter' => [
        'AllMySms' => [
            'package' => 'symfony/all-my-sms-notifier',
            'dsn' => 'allmysms://LOGIN:APIKEY@default?from=FROM',
        ],
        'AmazonSns' => [
            'package' => 'symfony/amazon-sns-notifier',
            'dsn' => 'sns://ACCESS_KEY:SECRET_KEY@default?region=REGION',
        ],
        'Clickatell' => [
            'package' => 'symfony/clickatell-notifier',
            'dsn' => 'clickatell://ACCESS_TOKEN@default?from=FROM',
        ],
        'Esendex' => [
            'package' => 'symfony/esendex-notifier',
            'dsn' => 'esendex://USER_NAME:PASSWORD@default?accountreference=ACCOUNT_REFERENCE&from=FROM',
        ],
        'FakeSms' => [
            'package' => 'symfony/fake-sms-notifier',
            'dsn' => 'fakesms+email://MAILER_SERVICE_ID?to=TO&from=FROM',
        ],
        'FreeMobile' => [
            'package' => 'symfony/free-mobile-notifier',
            'dsn' => 'freemobile://LOGIN:API_KEY@default?phone=PHONE',
        ],
        'GatewayApi' => [
            'package' => 'symfony/gateway-api-notifier',
            'dsn' => 'gatewayapi://TOKEN@default?from=FROM',
        ],
        'Infobip' => [
            'package' => 'symfony/infobip-notifier',
            'dsn' => 'infobip://AUTH_TOKEN@HOST?from=FROM',
        ],
        'Iqsms' => [
            'package' => 'symfony/iqsms-notifier',
            'dsn' => 'iqsms://LOGIN:PASSWORD@default?from=FROM',
        ],
        'LightSms' => [
            'package' => 'symfony/light-sms-notifier',
            'dsn' => 'lightsms://LOGIN:TOKEN@default?from=PHONE',
        ],
        'Mailjet' => [
            'package' => 'symfony/mailjet-notifier',
            'dsn' => 'mailjet://TOKEN@default?from=FROM',
        ],
        'MessageBird' => [
            'package' => 'symfony/message-bird-notifier',
            'dsn' => 'messagebird://TOKEN@default?from=FROM',
        ],
        'MessageMedia' => [
            'package' => 'symfony/message-media-notifier',
            'dsn' => 'messagemedia://API_KEY:API_SECRET@default?from=FROM',
        ],
        'Mobyt' => [
            'package' => 'symfony/mobyt-notifier',
            'dsn' => 'mobyt://USER_KEY:ACCESS_TOKEN@default?from=FROM',
        ],
        'Octopush' => [
            'package' => 'symfony/octopush-notifier',
            'dsn' => 'octopush://USERLOGIN:APIKEY@default?from=FROM&type=TYPE',
        ],
        'OvhCloud' => [
            'package' => 'symfony/ovh-cloud-notifier',
            'dsn' => 'ovhcloud://APPLICATION_KEY:APPLICATION_SECRET@default?consumer_key=CONSUMER_KEY&service_name=SERVICE_NAME',
        ],
        'Brevo' => [
            'package' => 'symfony/brevo-notifier',
            'dsn' => 'brevo://API_KEY@default?sender=PHONE',
        ],
        'Sms77' => [
            'package' => 'symfony/sms77-notifier',
            'dsn' => 'sms77://API_KEY@default?from=FROM',
        ],
        'Sinch' => [
            'package' => 'symfony/sinch-notifier',
            'dsn' => 'sinch://ACCOUNT_ID:AUTH_TOKEN@default?from=FROM',
        ],
        'Smsapi' => [
            'package' => 'symfony/smsapi-notifier',
            'dsn' => 'smsapi://TOKEN@default?from=FROM',
        ],
        'SmsBiuras' => [
            'package' => 'symfony/sms-biuras-notifier',
            'dsn' => 'smsbiuras://UID:API_KEY@default?from=FROM&test_mode=TEST_MODE',
        ],
        'Smsc' => [
            'package' => 'symfony/smsc-notifier',
            'dsn' => 'smsc://LOGIN:PASSWORD@default?from=FROM',
        ],
        'SpotHit' => [
            'package' => 'symfony/spot-hit-notifier',
            'dsn' => 'spothit://TOKEN@default?from=FROM',
        ],
        'Telnyx' => [
            'package' => 'symfony/telnyx-notifier',
            'dsn' => 'telnyx://API_KEY@default?from=FROM&messaging_profile_id=MESSAGING_PROFILE_ID',
        ],
        'TurboSms' => [
            'package' => 'symfony/turbo-sms-notifier',
            'dsn' => 'turbosms://AUTH_TOKEN@default?from=FROM',
        ],
        'Twilio' => [
            'package' => 'symfony/twilio-notifier',
            'dsn' => 'twilio://SID:TOKEN@default?from=FROM',
        ],
        'Vonage' => [
            'package' => 'symfony/vonage-notifier',
            'dsn' => 'vonage://KEY:SECRET@default?from=FROM',
        ],
        'Yunpian' => [
            'package' => 'symfony/yunpian-notifier',
            'dsn' => 'yunpian://APIKEY@default',
        ],
    ],
    'chatter' => [
        'AmazonSns' => [
            'package' => 'symfony/amazon-sns-notifier',
            'dsn' => 'sns://ACCESS_KEY:SECRET_KEY@default?region=REGION',
        ],
        'Discord' => [
            'package' => 'symfony/discord-notifier',
            'dsn' => 'discord://TOKEN@default?webhook_id=ID',
        ],
        'FakeChat' => [
            'package' => 'symfony/fake-chat-notifier',
            'dsn' => 'fakechat+email://default?to=TO&from=FROM',
        ],
        'Firebase' => [
            'package' => 'symfony/firebase-notifier',
            'dsn' => 'firebase://USERNAME:PASSWORD@default',
        ],
        'Gitter' => [
            'package' => 'symfony/gitter-notifier',
            'dsn' => 'gitter://TOKEN@default?room_id=ROOM_ID',
        ],
        'GoogleChat' => [
            'package' => 'symfony/google-chat-notifier',
            'dsn' => 'googlechat://ACCESS_KEY:ACCESS_TOKEN@default/SPACE?thread_key=THREAD_KEY',
        ],
        'LinkedIn' => [
            'package' => 'symfony/linked-in-notifier',
            'dsn' => 'linkedin://TOKEN:USER_ID@default',
        ],
        'Mattermost' => [
            'package' => 'symfony/mattermost-notifier',
            'dsn' => 'mattermost://ACCESS_TOKEN@HOST/PATH?channel=CHANNEL',
        ],
        'Mercure' => [
            'package' => 'symfony/mercure-notifier',
            'dsn' => 'mercure://HUB_ID?topic=TOPIC',
        ],
        'MicrosoftTeams' => [
            'package' => 'symfony/microsoft-teams-notifier',
            'dsn' => 'microsoftteams://default/PATH',
        ],
        'RocketChat' => [
            'package' => 'symfony/rocket-chat-notifier',
            'dsn' => 'rocketchat://TOKEN@ENDPOINT?channel=CHANNEL',
        ],
        'Slack' => [
            'package' => 'symfony/slack-notifier',
            'dsn' => 'slack://TOKEN@default?channel=CHANNEL',
        ],
        'Telegram' => [
            'package' => 'symfony/telegram-notifier',
            'dsn' => 'telegram://TOKEN@default?channel=CHAT_ID',
        ],
        'Zulip' => [
            'package' => 'symfony/zulip-notifier',
            'dsn' => 'zulip://EMAIL:TOKEN@HOST?channel=CHANNEL',
        ],
    ],
];
