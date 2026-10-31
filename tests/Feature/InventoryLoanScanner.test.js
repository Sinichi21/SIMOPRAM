import { test } from 'node:test';
import assert from 'node:assert/strict';
import { inventoryLoanScanner } from '../../resources/js/inventory-loan-scanner.js';

function cameraEnvironment(getUserMedia) {
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: { mediaDevices: { getUserMedia } } });
    globalThis.BarcodeDetector = class {
        async detect() { return [{ rawValue: 'UNIT-001' }]; }
    };
}

function scanner(wire) {
    return Object.assign(inventoryLoanScanner(wire), {
        $refs: { video: { srcObject: null, async play() {} } },
        async $nextTick() {},
    });
}

test('camera submits the QR once and releases its tracks', async () => {
    let stopped = 0;
    const calls = [];
    cameraEnvironment(async () => ({ getTracks: () => [{ stop: () => stopped++ }] }));
    const state = scanner({
        async $set(...args) { calls.push(args); },
        async findByCode() { calls.push('lookup'); },
    });

    await state.start();

    assert.deepEqual(calls, [['itemCode', 'UNIT-001', false], 'lookup']);
    assert.equal(stopped, 1);
    assert.equal(state.scanning, false);
    assert.equal(state.$refs.video.srcObject, null);
});

test('closing while camera permission is pending releases the acquired stream', async () => {
    let acquire;
    let stopped = 0;
    cameraEnvironment(() => new Promise(resolve => { acquire = resolve; }));
    const state = scanner({ findByCode() { assert.fail('No lookup after closing'); } });
    const starting = state.start();

    state.destroy();
    acquire({ getTracks: () => [{ stop: () => stopped++ }] });
    await starting;

    assert.equal(stopped, 1);
    assert.equal(state.scanning, false);
});

test('camera permission denial leaves a usable manual input fallback', async () => {
    cameraEnvironment(async () => { throw new Error('Permission denied'); });
    const state = scanner({});

    await state.start();

    assert.equal(state.scanning, false);
    assert.equal(state.starting, false);
    assert.match(state.message, /izin kamera/);
});
