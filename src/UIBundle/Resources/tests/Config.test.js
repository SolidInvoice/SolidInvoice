import {describe, it} from 'mocha';
import {config, config as config2} from '../assets/Config';
import {assert, expect} from 'chai';

describe('it creates a config', () => {
    it('creates a single config instance', () => {
        expect(config).to.equal(config, config2);
    });

    it('has empty config by default', () => {
        expect(config.get('accounting')).to.deep.equal({});
    });

    it('loads the config from the DOM', () => {
        config.load();

        expect(config.get('accounting')).to.deep.equal({"currency": {"symbol": "$", "format": "%s%v", "decimal": ".", "thousand": ",", "precision": 2}, "number": {"precision": 0, "thousand": ",", "decimal": "."}});
    });
});