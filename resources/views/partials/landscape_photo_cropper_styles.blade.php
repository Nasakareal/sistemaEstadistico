<style>
    .sv-crop-modal {
        position: fixed;
        inset: 0;
        z-index: 1085;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
        background: rgba(0, 0, 0, .78);
    }

    .sv-crop-modal.is-open {
        display: flex;
    }

    .sv-crop-dialog {
        width: min(720px, 100%);
        max-height: calc(100vh - 36px);
        display: flex;
        flex-direction: column;
        border-radius: 14px;
        overflow: hidden;
        background: #0b1220;
        box-shadow: 0 24px 80px rgba(0, 0, 0, .45);
    }

    .sv-crop-header,
    .sv-crop-footer {
        padding: 14px 16px;
        border-color: rgba(255, 255, 255, .12);
        color: #fff;
    }

    .sv-crop-header {
        border-bottom: 1px solid rgba(255, 255, 255, .12);
        font-weight: 700;
    }

    .sv-crop-body {
        padding: 16px;
        overflow: auto;
    }

    .sv-crop-stage {
        position: relative;
        width: fit-content;
        max-width: 100%;
        max-height: min(62vh, 680px);
        margin: 0 auto;
        overflow: hidden;
        touch-action: none;
        cursor: ns-resize;
        background: #000;
    }

    .sv-crop-stage img {
        display: block;
        max-width: 100%;
        max-height: min(62vh, 680px);
        user-select: none;
        pointer-events: none;
    }

    .sv-crop-selection {
        position: absolute;
        left: 0;
        right: 0;
        border: 3px solid #fff;
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, .42);
        pointer-events: none;
    }

    .sv-crop-selection::before,
    .sv-crop-selection::after {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        border-top: 1px solid rgba(255, 255, 255, .62);
    }

    .sv-crop-selection::before {
        top: 33.333%;
    }

    .sv-crop-selection::after {
        top: 66.666%;
    }

    .sv-crop-help {
        margin: 12px 0 8px;
        color: rgba(255, 255, 255, .72);
        text-align: center;
        font-size: 14px;
    }

    .sv-crop-range {
        width: 100%;
    }

    .sv-crop-footer {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        border-top: 1px solid rgba(255, 255, 255, .12);
        background: rgba(255, 255, 255, .03);
    }
</style>
