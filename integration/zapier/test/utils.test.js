'use strict';

const { toCents, fromCents } = require('../utils/money');
const { resolveIriId, makeSyntheticId } = require('../utils/pagination');

describe('utils/money', () => {
  it('converts decimal strings to cents', () => {
    expect(toCents('100.50')).toBe(10050);
    expect(toCents('0.99')).toBe(99);
    expect(toCents(0)).toBe(0);
  });

  it('rounds fractional cents to the nearest integer', () => {
    expect(toCents('0.015')).toBe(2);
  });

  it('handles null and empty input', () => {
    expect(toCents(null)).toBeNull();
    expect(toCents('')).toBeNull();
  });

  it('throws on non-numeric input', () => {
    expect(() => toCents('abc')).toThrow(/Invalid money amount/);
  });

  it('converts cents back to decimal', () => {
    expect(fromCents(10050)).toBe(100.5);
    expect(fromCents(null)).toBeNull();
  });
});

describe('utils/pagination', () => {
  it('extracts the trailing id from an IRI', () => {
    expect(resolveIriId('/api/clients/01ABC')).toBe('01ABC');
    expect(resolveIriId('01ABC')).toBe('01ABC');
    expect(resolveIriId(null)).toBeNull();
  });

  it('builds stable synthetic ids', () => {
    const item = { '@id': '/api/invoices/X', id: 'X' };
    expect(makeSyntheticId(item, 'paid', '2026-01-01')).toBe('/api/invoices/X|paid|2026-01-01');
  });
});
