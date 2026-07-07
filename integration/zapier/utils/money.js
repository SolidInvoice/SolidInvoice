'use strict';

const toCents = (amount) => {
  if (amount === null || amount === undefined || amount === '') return null;
  const num = typeof amount === 'string' ? parseFloat(amount) : amount;
  if (Number.isNaN(num)) {
    throw new Error(`Invalid money amount: ${amount}`);
  }
  return Math.round(num * 100);
};

const fromCents = (cents) => {
  if (cents === null || cents === undefined) return null;
  const num = typeof cents === 'string' ? parseInt(cents, 10) : cents;
  if (Number.isNaN(num)) return null;
  return num / 100;
};

module.exports = { toCents, fromCents };
