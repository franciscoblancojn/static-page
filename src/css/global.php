<style>
    .tab-content:not(.nav-tab-active) {
        display: none;
    }
    .tab-content {
        padding-top: 1rem;
    }
    .nav-tab {
        cursor: pointer;
    }
    .stpa-message {
        font-weight: 900;
        position: sticky;
        left: 0;
        top: 2.5rem;
        padding: 1rem;
        border-radius: .5rem;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        z-index: 10;
    }
    .stpa-message.error {
        color: #fff;
        background: #d63638;
    }
    .stpa-message.ok {
        color: #fff;
        background: #25992f;
    }
    [type="submit"].stpa-loader {
        position: relative;
        color: transparent !important;
    }
    [type="submit"].stpa-loader::after {
        content: '';
        display: block;
        position: absolute;
        inset: 0;
        margin: auto;
        width: 1rem;
        height: 1rem;
        aspect-ratio: 1/1;
        border-radius: 100%;
        border: 2px solid #1d2327;
        border-top-color: transparent;
        animation: stpa-rotate 1s infinite;
    }
    [type="submit"].button-primary.stpa-loader::after {
        border: 2px solid #fff;
        border-top-color: transparent;
    }
    @keyframes stpa-rotate {
        to { transform: rotateZ(360deg); }
    }
    .content-btn {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .content-title-btn {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
    }
    .content-btn .submit {
        margin: 0;
        padding: 0;
    }
    .stpa-tooltip {
        position: relative;
        cursor: pointer;
        margin-left: 6px;
        display: inline-block;
    }
    .stpa-tooltip-text::after {
        content: "";
        position: absolute;
        top: 100%;
        left: 10px;
        border-width: 5px;
        border-style: solid;
        border-color: #1d2327 transparent transparent transparent;
    }
    .stpa-tooltip-text {
        visibility: hidden;
        opacity: 0;
        width: 360px;
        background: #1d2327;
        color: #fff;
        text-align: left;
        padding: 8px;
        border-radius: 6px;
        position: absolute;
        z-index: 9999;
        bottom: 125%;
        left: 0;
        transition: opacity 0.2s ease;
        font-size: 12px;
        line-height: 1.4;
    }
    .stpa-tooltip:hover .stpa-tooltip-text {
        visibility: visible;
        opacity: 1;
    }
    .stpa-table {
        width: 100%;
        border-collapse: collapse;
    }
    .stpa-table th,
    .stpa-table td {
        padding: 10px 12px;
        text-align: left;
        vertical-align: middle;
    }
    .stpa-table th {
        background: #f0f0f1;
        font-weight: 600;
        border-bottom: 2px solid #c3c4c7;
    }
    .stpa-table td {
        border-bottom: 1px solid #e0e0e0;
    }
    .stpa-table tr:hover td {
        background: #f6f7f7;
    }
    .stpa-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .stpa-status.active {
        background: #d5f5e3;
        color: #1a7d36;
    }
    .stpa-status.inactive {
        background: #fce8e8;
        color: #b32d2e;
    }
    .stpa-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .stpa-actions .button {
        min-height: 30px;
        line-height: 28px;
        font-size: 12px;
    }
    .stpa-file-info {
        font-size: 12px;
        color: #666;
    }
    .stpa-bulk-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-bottom: 1rem;
        padding: 0;
        background: #f0f0f1;
        border-radius: 4px;
    }
    .stpa-bulk-actions select {
        min-width: 180px;
    }
    .stpa-filters {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-bottom: 1rem;
        padding: 0;
        background: #f0f0f1;
        border-radius: 4px;
        flex-wrap: wrap;
    }
    .stpa-filters .stpa-search-input {
        flex: 1;
        min-width: 200px;
    }
    .stpa-filter-count {
        margin-left: auto;
        font-size: 12px;
        color: #666;
    }
    .stpa-group-header {
        cursor: pointer;
        background: #e8e8eb !important;
        user-select: none;
    }
    .stpa-group-header:hover td {
        background: #ddd !important;
    }
    .stpa-group-header td {
        padding: 8px 12px !important;
        font-size: 13px;
        border-bottom: 1px solid #c3c4c7;
    }
    .stpa-group-toggle {
        font-size: 16px;
        width: 16px;
        height: 16px;
        margin-right: 6px;
        vertical-align: middle;
    }
    .stpa-child-icon {
        font-size: 12px;
        width: 12px;
        height: 12px;
        margin-right: 2px;
        vertical-align: middle;
        color: #999;
    }
    .stpa-badge {
        display: inline-block;
        background: #2271b1;
        color: #fff;
        font-size: 11px;
        padding: 1px 8px;
        border-radius: 10px;
        margin-left: 6px;
    }
    @media screen and (max-width: 782px) {
        .stpa-table th,
        .stpa-table td {
            padding: 6px 8px;
            font-size: 13px;
        }
        .stpa-actions {
            flex-direction: column;
        }
    }
    .stpa-page-row.stpa-page-row.stpa-page-row{
        background: transparent;
    }
    .stpa-group{
        background: #c3c4c731;
    }
    .stpa-gs-view-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.6);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }
    .stpa-gs-view-modal-overlay.active {
        display: flex;
    }
    .stpa-gs-view-modal-box {
        background: #fff;
        border-radius: 8px;
        max-width: 800px;
        width: 90%;
        max-height: 80vh;
        overflow: auto;
        padding: 20px;
    }
    .stpa-gs-view-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    .stpa-gs-view-modal-header h3 {
        margin: 0;
    }
    .stpa-gs-view-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }
    .stpa-gs-view-modal-content {
        background: #f0f0f1;
        padding: 16px;
        border-radius: 4px;
        overflow: auto;
        font-size: 12px;
        line-height: 1.5;
        max-height: 60vh;
        white-space: pre-wrap;
        word-break: break-all;
    }
    #stpa-gs-create-msg {
        display: none;
        padding: 8px 12px;
        border-radius: 4px;
        font-weight: 600;
    }
    #stpa-gs-create-msg.ok {
        display: inline-block;
        color: #fff;
        background: #25992f;
    }
    #stpa-gs-create-msg.error {
        display: inline-block;
        color: #fff;
        background: #d63638;
    }
    .stpa-gs-loader {
        position: relative;
        color: transparent !important;
        pointer-events: none;
    }
    .stpa-gs-loader::after {
        content: '';
        display: block;
        position: absolute;
        inset: 0;
        margin: auto;
        width: 1rem;
        height: 1rem;
        aspect-ratio: 1/1;
        border-radius: 100%;
        border: 2px solid #1d2327;
        border-top-color: transparent;
        animation: stpa-rotate 1s infinite;
    }
    .stpa-gs-loader.button-primary::after {
        border-color: #fff;
        border-top-color: transparent;
    }
</style>
