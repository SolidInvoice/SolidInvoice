<?php

use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config): void {
    $config->notifier()
        ->enabled(true)
        ->chatterTransport('Discord', null);
};

/*
framework:
    notifier:
        chatter_transports:
            zulip: '%env(ZULIP_DSN)%'
            telegram: '%env(TELEGRAM_DSN)%'
            slack: '%env(SLACK_DSN)%'
            rocketchat: '%env(ROCKETCHAT_DSN)%'
            microsoftteams: '%env(MICROSOFT_TEAMS_DSN)%'
            mercure: '%env(MERCURE_DSN)%'
            mattermost: '%env(MATTERMOST_DSN)%'
            linkedin: '%env(LINKEDIN_DSN)%'
            googlechat: '%env(GOOGLE_CHAT_DSN)%'
            gitter: '%env(GITTER_DSN)%'
            firebase: '%env(FIREBASE_DSN)%'
            fakechat+email: '%env(FAKE_CHAT_DSN)%'
            discord: '%env(DISCORD_DSN)%'
            sns: '%env(AMAZON_SNS_DSN)%'
        texter_transports:
            yunpian: '%env(YUNPIAN_DSN)%'
            vonage: '%env(VONAGE_DSN)%'
            twilio: '%env(TWILIO_DSN)%'
            turbosms: '%env(TURBOSMS_DSN)%'
            telnyx: '%env(TELNYX_DSN)%'
            spothit: '%env(SPOTHIT_DSN)%'
            smsc: '%env(SMSC_DSN)%'
            smsapi: '%env(SMSAPI_DSN)%'
            sms77: '%env(SMS77_DSN)%'
            smsbiuras: '%env(SMSBIURAS_DSN)%'
            sinch: '%env(SINCH_DSN)%'
            sendinblue: '%env(SENDINBLUE_DSN)%'
            ovhcloud: '%env(OVHCLOUD_DSN)%'
            octopush: '%env(OCTOPUSH_DSN)%'
            nexmo: '%env(NEXMO_DSN)%'
            mobyt: '%env(MOBYT_DSN)%'
            messagemedia: '%env(MESSAGEMEDIA_DSN)%'
            messagebird: '%env(MESSAGEBIRD_DSN)%'
            mailjet: '%env(MAILJET_DSN)%'
            lightsms: '%env(LIGHTSMS_DSN)%'
            iqsms: '%env(IQSMS_DSN)%'
            infobip: '%env(INFOBIP_DSN)%'
            gatewayapi: '%env(GATEWAYAPI_DSN)%'
            freemobile: '%env(FREE_MOBILE_DSN)%'
            fakesms+email: '%env(FAKE_SMS_DSN)%'
            esendex: '%env(ESENDEX_DSN)%'
            clickatell: '%env(CLICKATELL_DSN)%'
            allmysms: '%env(ALLMYSMS_DSN)%'
        channel_policy:
            # use chat/slack, chat/telegram, sms/twilio or sms/nexmo
            urgent: ['email']
            high: ['email']
            medium: ['email']
            low: ['email']
        admin_recipients:
            - { email: admin@example.com }
*/
