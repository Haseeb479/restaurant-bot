<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Register — {{ $restaurant->name }} | Foodio</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-canvas: #ECEFE9;
            --bg-card: rgba(248, 249, 247, 0.92);
            --border-subtle: #F2F4F1;
            --border-card: #F2F4F1;
            --color-forest: #325744;
            --color-forest-hover: #264334;
            --color-forest-light: #E6ECE8;
            --color-ochre: #C29842;
            --color-amber: #C29842;
            --color-coral: #A64444;
            --color-terracotta: #A64444;
            --color-sage: #689780;
            --color-teal: #648C94;
            --color-blue: #5B7896;
            --color-green: #325744;
            --color-dark: #18221D;
            --color-gray: #68736D;
            --text-heading: #18221D;
            --text-body: #374151;
            --text-muted: #68736D;
            --text-light: #8E9993;
            --inset-pill-bg: #E6ECE8;
            --popover-bg: #FFFFFF;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        /* ── ROOT APPLICATION SHELL & RESPONSIVE SCROLL RULES ── */
        html, body {
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: var(--bg-canvas);
            color: var(--text-body);
        }

        /* Desktop Viewports (1024px and above) — STRICT NO-SCROLL SHELL */
        @media (min-width: 1024px) {
            html, body {
                height: 100dvh;
                max-height: 100dvh;
                width: 100vw;
                overflow: hidden !important;
                display: flex;
                flex-direction: column;
            }
        }

        /* Tablet Viewports (768px to 1023px) — Fixed viewport cashier register */
        @media (min-width: 768px) and (max-width: 1023px) {
            html, body {
                height: 100dvh;
                max-height: 100dvh;
                width: 100vw;
                overflow: hidden !important;
                display: flex;
                flex-direction: column;
            }
        }

        /* Mobile Screens (Under 768px) — Natural vertical scrolling */
        @media (max-width: 767px) {
            html, body {
                height: auto !important;
                min-height: 100%;
                overflow-x: hidden !important;
                overflow-y: auto !important;
                display: block;
            }
        }

        /* ── TOP FLOATING HEADER ISLAND ── */
        .pos-top-header {
            height: 56px;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.90);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border-subtle);
            flex-shrink: 0;
            z-index: 50;
        }

        .pos-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pos-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--bg-canvas);
            border: 1px solid var(--border-subtle);
            padding: 6px 14px;
            border-radius: 9999px;
            color: var(--text-heading);
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.16s ease;
        }
        .pos-back-btn:hover {
            background: var(--color-forest);
            color: #ffffff;
            border-color: var(--color-forest);
            transform: translateX(-2px);
        }

        .pos-brand-badge {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pos-brand-squircle {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: var(--color-forest);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 800;
            box-shadow: 0 3px 10px rgba(17,66,50,0.20);
        }
        .pos-brand-title {
            font-size: 14px;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .pos-brand-sub {
            font-size: 11px;
            color: var(--text-muted);
            display: block;
        }

        /* Mode Switcher Capsule */
        .pos-mode-dock {
            display: flex;
            background: var(--bg-canvas);
            border-radius: 9999px;
            padding: 3px;
            border: 1px solid var(--border-subtle);
            gap: 3px;
        }
        .pos-mode-btn {
            padding: 6px 14px;
            border-radius: 9999px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.16s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .pos-mode-btn.active {
            background: var(--color-forest);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(17,66,50,0.2);
        }

        .pos-header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .pos-live-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--color-forest-light);
            color: var(--color-forest);
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 11.5px;
            font-weight: 700;
        }
        .pos-live-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10B981;
            box-shadow: 0 0 0 2px rgba(16,185,129,0.3);
            animation: pulseDot 2s infinite;
        }
        @keyframes pulseDot {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.15); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        /* ── WORKSPACE 2-COLUMN GRID (Fixed Viewport Shell) ── */
        .pos-workspace {
            display: grid;
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }

        @media (min-width: 1024px) {
            .pos-workspace {
                grid-template-columns: 1fr 390px;
                height: calc(100dvh - 56px);
                max-height: calc(100dvh - 56px);
            }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            .pos-workspace {
                grid-template-columns: 1fr 330px;
                height: calc(100dvh - 56px);
                max-height: calc(100dvh - 56px);
            }
        }

        @media (max-width: 767px) {
            .pos-workspace {
                display: flex !important;
                flex-direction: column !important;
                height: auto !important;
                min-height: auto !important;
                overflow: visible !important;
            }
        }

        /* LEFT: DISH CATALOG & SEARCH */
        .pos-catalog-panel {
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
            background: var(--bg-canvas);
            overflow: hidden;
        }

        .pos-catalog-topbar {
            padding: 10px 18px 8px;
            background: #ffffff;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-shrink: 0;
        }

        .pos-search-wrapper {
            position: relative;
            width: 100%;
        }
        .pos-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }
        .pos-search-input {
            width: 100%;
            padding: 9px 16px 9px 40px;
            border-radius: 12px;
            border: 1px solid var(--border-subtle);
            background: var(--bg-canvas);
            font-size: 13px;
            color: var(--text-heading);
            outline: none;
            transition: all 0.18s ease;
            font-family: inherit;
        }
        .pos-search-input:focus {
            border-color: var(--color-forest);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(17,66,50,0.08);
        }
        .pos-search-clear {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 15px;
            cursor: pointer;
            display: none;
            padding: 4px;
        }

        /* Category Filter Pills */
        .pos-categories-strip {
            display: flex;
            align-items: center;
            gap: 6px;
            overflow-x: auto;
            padding-bottom: 3px;
            scrollbar-width: thin;
        }
        .pos-categories-strip::-webkit-scrollbar { height: 3px; }
        .pos-categories-strip::-webkit-scrollbar-thumb {
            background: var(--border-card);
            border-radius: 3px;
        }
        .pos-cat-pill {
            padding: 6px 14px;
            border-radius: 9999px;
            border: 1px solid var(--border-subtle);
            background: var(--bg-canvas);
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.16s ease;
        }
        .pos-cat-pill:hover {
            border-color: var(--color-forest);
            color: var(--text-heading);
            background: #ffffff;
        }
        .pos-cat-pill.active {
            background: var(--color-forest);
            color: #ffffff;
            border-color: var(--color-forest);
            box-shadow: 0 2px 8px rgba(17,66,50,0.20);
        }

        /* Dedicated Internal Scroll Area: Dishes Grid */
        .pos-dishes-grid {
            flex: 1 1 0%;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 12px 18px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
            gap: 12px;
            align-content: start;
        }
        .pos-dishes-grid::-webkit-scrollbar { width: 5px; }
        .pos-dishes-grid::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 6px;
        }

        .pos-dish-card {
            background: #ffffff;
            border: 1px solid var(--border-card);
            border-radius: 16px;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 108px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            position: relative;
            user-select: none;
        }
        .pos-dish-card:hover {
            transform: translateY(-2px);
            border-color: var(--color-forest);
            box-shadow: 0 8px 18px rgba(17,66,50,0.08);
        }
        .pos-dish-card:active {
            transform: scale(0.98);
        }

        .pos-dish-title {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text-heading);
            line-height: 1.3;
            margin-bottom: 2px;
        }
        .pos-dish-cat {
            font-size: 10.5px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .pos-dish-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
        }
        .pos-dish-price {
            font-size: 14.5px;
            font-weight: 800;
            color: var(--color-forest);
        }
        .pos-dish-add-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--color-forest-light);
            color: var(--color-forest);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 800;
            transition: all 0.16s ease;
        }
        .pos-dish-card:hover .pos-dish-add-circle {
            background: var(--color-forest);
            color: #ffffff;
            transform: scale(1.08);
        }

        .pos-dish-sizes-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(72px, 1fr));
            gap: 5px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed var(--border-subtle);
        }
        .pos-dish-size-pill {
            padding: 4px 6px;
            border-radius: 9px;
            border: 1px solid var(--border-subtle);
            background: var(--bg-canvas);
            color: var(--text-heading);
            cursor: pointer;
            transition: all 0.14s ease;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            line-height: 1.15;
            user-select: none;
        }
        .pos-dish-size-pill:hover {
            background: var(--color-forest);
            color: #ffffff;
            border-color: var(--color-forest);
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(17,66,50,0.18);
        }
        .pos-dish-size-pill .pill-size-name {
            font-size: 10.5px;
            font-weight: 800;
            display: block;
        }
        .pos-dish-size-pill .pill-size-price {
            font-size: 9.5px;
            font-weight: 700;
            color: var(--color-forest);
            display: block;
        }
        .pos-dish-size-pill:hover .pill-size-price {
            color: #ffffff;
            opacity: 0.95;
        }

        /* ── DEDICATED PIZZA / DISH SIZE SELECTION MODAL ── */
        .pos-size-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(24, 34, 29, 0.50);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: posModalFade 0.18s ease both;
        }
        @keyframes posModalFade {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        .pos-size-modal-card {
            background: #ffffff;
            border-radius: 26px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 480px;
            padding: 26px 28px;
            border: 1px solid var(--border-subtle);
            animation: posModalPop 0.22s cubic-bezier(0.16, 1, 0.3, 1) both;
            box-sizing: border-box;
        }
        @keyframes posModalPop {
            from { transform: scale(0.92) translateY(12px); opacity: 0; }
            to   { transform: scale(1) translateY(0); opacity: 1; }
        }
        .pos-size-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .pos-size-modal-cat {
            font-size: 11px;
            font-weight: 800;
            color: var(--color-forest);
            background: var(--color-forest-light);
            padding: 3px 10px;
            border-radius: 9999px;
            display: inline-block;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .pos-size-modal-title {
            font-size: 21px;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.02em;
            line-height: 1.25;
        }
        .pos-size-modal-close {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1px solid var(--border-subtle);
            background: var(--bg-canvas);
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }
        .pos-size-modal-close:hover {
            background: var(--color-forest);
            color: #ffffff;
        }
        .pos-size-modal-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }
        .pos-size-modal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .pos-size-option-card {
            background: var(--bg-canvas);
            border: 2px solid transparent;
            border-radius: 18px;
            padding: 18px 14px;
            text-align: center;
            cursor: pointer;
            transition: all 0.16s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 6px;
            user-select: none;
        }
        .pos-size-option-card:hover {
            border-color: var(--color-forest);
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(17, 66, 50, 0.12);
        }
        .pos-size-option-shortcut {
            font-size: 10px;
            font-weight: 800;
            background: rgba(0, 0, 0, 0.06);
            color: var(--text-muted);
            padding: 2px 7px;
            border-radius: 5px;
            margin-bottom: 2px;
        }
        .pos-size-option-name {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.01em;
        }
        .pos-size-option-price {
            font-size: 17px;
            font-weight: 800;
            color: var(--color-forest);
        }
        .pos-size-modal-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px solid var(--border-subtle);
        }
        .pos-size-modal-cancel {
            padding: 8px 20px;
            border-radius: 9999px;
            border: 1px solid var(--border-subtle);
            background: var(--bg-canvas);
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-body);
            cursor: pointer;
            transition: all 0.14s ease;
        }
        .pos-size-modal-cancel:hover {
            background: #ffffff;
            color: var(--text-heading);
        }

        /* RIGHT: CASHIER REGISTER SLIP (Fixed Viewport Shell with Internal Scroll) */
        .pos-slip-panel {
            background: #ffffff;
            border-left: 1px solid var(--border-subtle);
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
            overflow: hidden;
            box-shadow: -4px 0 20px rgba(0,0,0,0.02);
        }

        .pos-slip-header {
            padding: 10px 16px 8px;
            border-bottom: 1px solid var(--border-subtle);
            background: #ffffff;
            flex-shrink: 0;
        }
        .pos-slip-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .pos-slip-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.02em;
        }
        .pos-slip-seq {
            background: var(--color-forest-light);
            color: var(--color-forest);
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
        }

        .pos-guest-inputs-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 6px;
        }
        .pos-field-input {
            width: 100%;
            padding: 7px 10px;
            border-radius: 10px;
            border: 1px solid var(--border-subtle);
            background: var(--bg-canvas);
            font-size: 12px;
            color: var(--text-heading);
            outline: none;
            font-family: inherit;
        }
        .pos-field-input:focus {
            border-color: var(--color-forest);
            background: #ffffff;
        }

        /* Dedicated Internal Scroll Area: Cart Items List */
        .pos-slip-items {
            flex: 1 1 0%;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 8px 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .pos-slip-items::-webkit-scrollbar { width: 5px; }
        .pos-slip-items::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 5px;
        }

        .pos-empty-slip {
            text-align: center;
            padding: 40px 16px;
            color: var(--text-muted);
        }
        .pos-empty-icon {
            font-size: 34px;
            margin-bottom: 8px;
        }
        .pos-empty-h {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 3px;
        }
        .pos-empty-p {
            font-size: 11.5px;
            color: var(--text-muted);
            max-width: 220px;
            margin: 0 auto;
            line-height: 1.4;
        }

        .pos-cart-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            background: var(--bg-canvas);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            gap: 8px;
        }
        .pos-cart-info {
            flex: 1;
            min-width: 0;
        }
        .pos-cart-name {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-heading);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .pos-cart-price-sub {
            font-size: 10.5px;
            color: var(--text-muted);
            margin-top: 1px;
        }

        .pos-cart-stepper {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 9999px;
            padding: 2px 6px;
        }
        .pos-stepper-btn {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: none;
            background: transparent;
            color: var(--text-heading);
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            transition: all 0.12s ease;
        }
        .pos-stepper-btn:hover {
            background: var(--color-forest-light);
            color: var(--color-forest);
        }
        .pos-stepper-qty {
            font-size: 12px;
            font-weight: 800;
            min-width: 16px;
            text-align: center;
        }

        .pos-cart-total {
            font-size: 12.5px;
            font-weight: 800;
            color: var(--color-forest);
            min-width: 60px;
            text-align: right;
        }
        .pos-cart-del {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 14px;
            cursor: pointer;
            padding: 3px;
            transition: color 0.15s ease;
        }
        .pos-cart-del:hover {
            color: #EF4444;
        }

        /* Permanently Pinned Slip Calculations & Payment Footer */
        .pos-slip-footer {
            padding: 10px 16px 14px;
            border-top: 1px solid var(--border-subtle);
            background: #ffffff;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex-shrink: 0 !important;
            box-shadow: 0 -4px 14px rgba(0, 0, 0, 0.03);
            z-index: 10;
        }

        .pos-math-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-muted);
        }
        .pos-math-row.grand-total {
            margin-top: 4px;
            padding-top: 6px;
            border-top: 1px dashed var(--border-subtle);
        }
        .pos-total-amount {
            font-size: 21px;
            font-weight: 800;
            color: var(--color-forest);
            letter-spacing: -0.02em;
        }

        /* Payment Mode Selector */
        .pos-pay-strip {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 5px;
            margin-top: 2px;
        }
        .pos-pay-btn {
            padding: 6px 4px;
            border-radius: 10px;
            border: 1px solid var(--border-subtle);
            background: var(--bg-canvas);
            color: var(--text-body);
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            transition: all 0.16s ease;
        }
        .pos-pay-btn.active {
            border-color: var(--color-forest);
            background: var(--color-forest-light);
            color: var(--color-forest);
        }

        /* Cash Tender Row */
        .pos-tender-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 10px;
            background: var(--bg-canvas);
            border: 1px solid var(--border-subtle);
        }
        .pos-tender-input {
            width: 100%;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid var(--border-subtle);
            background: #ffffff;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-heading);
            outline: none;
        }
        .pos-change-val {
            font-size: 14px;
            font-weight: 800;
            color: var(--color-forest);
        }

        /* Primary Punch & Print Button */
        .pos-punch-btn {
            width: 100%;
            padding: 11px 16px;
            border-radius: 9999px;
            border: none;
            background: var(--color-forest);
            color: #ffffff;
            font-size: 13.5px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(17,66,50,0.22);
            transition: all 0.18s ease;
            letter-spacing: -0.01em;
        }
        .pos-punch-btn:hover {
            background: var(--color-forest-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(17,66,50,0.30);
        }
        .pos-punch-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Floating Toast */
        .pos-toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            background: #114232;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 9999px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            font-weight: 700;
            font-size: 13px;
            z-index: 99999;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: toastPop 0.25s ease both;
        }
        @keyframes toastPop {
            from { transform: translateX(-50%) translateY(12px); opacity: 0; }
            to   { transform: translateX(-50%) translateY(0); opacity: 1; }
        }

        /* ── RESPONSIVE MOBILE STYLES (< 768px) ── */
        @media (max-width: 767px) {
            .pos-top-header {
                height: auto;
                min-height: 52px;
                padding: 10px 14px;
                flex-wrap: wrap;
                gap: 10px;
            }
            .pos-header-left {
                width: 100%;
                justify-content: space-between;
            }
            .pos-mode-dock {
                width: 100%;
                justify-content: center;
            }
            .pos-mode-btn {
                flex: 1;
                justify-content: center;
                text-align: center;
            }
            .pos-header-right {
                display: none;
            }

            .pos-catalog-panel {
                height: auto !important;
                overflow: visible !important;
            }
            .pos-catalog-topbar {
                position: sticky;
                top: 0;
                z-index: 30;
                padding: 10px 14px;
            }
            .pos-dishes-grid {
                overflow: visible !important;
                max-height: none !important;
                grid-template-columns: repeat(2, 1fr);
                padding: 10px 14px;
                gap: 10px;
            }
            .pos-dish-card {
                min-height: 100px;
                padding: 10px 12px;
                border-radius: 14px;
            }

            .pos-slip-panel {
                border-left: none;
                border-top: 2px solid var(--border-subtle);
                height: auto !important;
                overflow: visible !important;
            }
            .pos-slip-items {
                max-height: 280px;
                overflow-y: auto;
            }
            .pos-slip-footer {
                position: sticky;
                bottom: 0;
                z-index: 40;
                background: #ffffff;
                box-shadow: 0 -4px 16px rgba(0,0,0,0.08);
                padding: 12px 14px 16px;
            }

            .pos-mobile-slip-jump {
                display: none;
                position: fixed;
                bottom: 18px;
                right: 18px;
                z-index: 60;
                background: var(--color-forest);
                color: #ffffff;
                border: none;
                padding: 10px 16px;
                border-radius: 9999px;
                font-size: 13px;
                font-weight: 800;
                box-shadow: 0 6px 20px rgba(0,0,0,0.25);
                cursor: pointer;
                align-items: center;
                gap: 8px;
                text-decoration: none;
            }
        }
    </style>
</head>
<body>

    <!-- ── TOP NAVIGATION BAR ── -->
    <header class="pos-top-header">
        <div class="pos-header-left">
            <a href="{{ route('dashboard.orders', $restaurant->id) }}" class="pos-back-btn">
                <span>←</span>
                <span>Back to Dashboard</span>
            </a>
            <div class="pos-brand-badge">
                <div class="pos-brand-squircle">⚡</div>
                <div>
                    <h2 class="pos-brand-title">{{ $restaurant->name }} POS</h2>
                    <span class="pos-brand-sub">Walk-in Counter & Thermal Print Station</span>
                </div>
            </div>
        </div>

        <!-- Mode Segmented Switcher -->
        <div class="pos-mode-dock">
            <button type="button" class="pos-mode-btn active" id="btnModeTakeaway" onclick="switchPosMode('takeaway', this)">
                <span>🥡</span>
                <span>Walk-in Takeaway</span>
            </button>
            <button type="button" class="pos-mode-btn" id="btnModeDineIn" onclick="switchPosMode('dine_in', this)">
                <span>🍽️</span>
                <span>Dine-In Table</span>
            </button>
        </div>

        <div class="pos-header-right">
            <div class="pos-live-pill">
                <span class="pos-live-dot"></span>
                <span>● Terminal Online</span>
            </div>
            <span style="font-size: 13px; font-weight: 700; color: var(--text-heading);" id="posClock"></span>
        </div>
    </header>

    <!-- ── MAIN 2-COLUMN WORKSPACE ── -->
    <main class="pos-workspace">

        <!-- LEFT COLUMN: MENU CATALOG -->
        <section class="pos-catalog-panel">
            <div class="pos-catalog-topbar">
                <div class="pos-search-wrapper">
                    <svg class="pos-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input type="text" id="posSearch" class="pos-search-input" placeholder="Search dishes (e.g. smash burger, roll, pizza, shake)..." oninput="handlePosSearch()">
                    <button type="button" id="posSearchClear" class="pos-search-clear" onclick="clearPosSearch()">✕</button>
                </div>

                <!-- Categories Strip -->
                <div class="pos-categories-strip" id="posCategoriesStrip">
                    <button type="button" class="pos-cat-pill active" onclick="filterCategory('all', this)">
                        All Dishes ({{ $menuItems->count() }})
                    </button>
                    @foreach($categories as $cat)
                        <button type="button" class="pos-cat-pill" onclick="filterCategory('{{ $cat->id }}', this)">
                            {{ $cat->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Dishes Grid -->
            <div class="pos-dishes-grid" id="posDishesGrid">
                @forelse($menuItems as $dish)
                    @php
                        $catId = $dish->category_id ?? 'uncat';
                        $hasSizes = !empty($dish->sizes) && is_array($dish->sizes) && count($dish->sizes) > 0;
                        $basePrice = (float)($dish->price ?? ($hasSizes ? ($dish->sizes[0]['price'] ?? 0) : 0));
                    @endphp
                    <div class="pos-dish-card" 
                         data-id="{{ $dish->id }}" 
                         data-name="{{ strtolower($dish->name) }}" 
                         data-cat="{{ $catId }}"
                         onclick="onDishCardClick({{ $dish->id }})">
                        <div>
                            <div class="pos-dish-title">{{ $dish->name }}</div>
                            <div class="pos-dish-cat">{{ $dish->category->name ?? 'Dish' }}</div>
                        </div>

                        <div>
                            <div class="pos-dish-bottom">
                                <span class="pos-dish-price">
                                    @if($hasSizes)
                                        <span style="font-size: 10px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">From</span>
                                    @endif
                                    Rs. {{ number_format($basePrice) }}
                                </span>
                                <div class="pos-dish-add-circle" title="{{ $hasSizes ? 'Click to Choose Size' : 'Add to Slip' }}">
                                    {{ $hasSizes ? '⚡' : '+' }}
                                </div>
                            </div>

                            @if($hasSizes)
                                <div class="pos-dish-sizes-row" onclick="event.stopPropagation()">
                                    @foreach($dish->sizes as $s)
                                        <button type="button" class="pos-dish-size-pill" 
                                                title="Add {{ $s['size'] ?? $s['name'] }} (Rs. {{ number_format($s['price']) }})"
                                                onclick="addDishDirect({{ $dish->id }}, '{{ addslashes($dish->name) }}', {{ (float)($s['price'] ?? 0) }}, '{{ addslashes($s['size'] ?? $s['name'] ?? '') }}')">
                                            <span class="pill-size-name">{{ $s['size'] ?? $s['name'] }}</span>
                                            <span class="pill-size-price">Rs. {{ number_format($s['price']) }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px; color: var(--text-muted);">
                        No dishes found in menu catalog. Please add items in Menu management.
                    </div>
                @endforelse
            </div>
        </section>

        <!-- RIGHT COLUMN: CASHIER REGISTER SLIP -->
        <aside class="pos-slip-panel">
            <div class="pos-slip-header">
                <div class="pos-slip-title-row">
                    <span class="pos-slip-title">Order Slip</span>
                    <span class="pos-slip-seq" id="posSeqBadge">#{{ $todayOrdersCount + 1 }}</span>
                </div>

                <div class="pos-guest-inputs-grid">
                    <input type="text" id="posCustomerName" class="pos-field-input" placeholder="Guest Name" value="Counter Guest">
                    <input type="text" id="posCustomerPhone" class="pos-field-input" placeholder="Phone (optional)">
                </div>

                <div id="posTableFieldWrap" style="margin-top: 8px; display: none;">
                    <input type="text" id="posTableNumber" class="pos-field-input" placeholder="Table Number (e.g. Table 4)">
                </div>
            </div>

            <!-- Itemized Cart Slip -->
            <div class="pos-slip-items" id="posSlipItems">
                <div class="pos-empty-slip">
                    <div class="pos-empty-icon">🧾</div>
                    <div class="pos-empty-h">Slip is empty</div>
                    <p class="pos-empty-p">Click any dish on the left to add items to this counter bill.</p>
                </div>
            </div>

            <!-- Calculation & Checkout Footer -->
            <div class="pos-slip-footer">
                <div class="pos-math-row">
                    <span>Subtotal</span>
                    <strong id="posSubtotalVal">Rs. 0</strong>
                </div>
                <div class="pos-math-row">
                    <span>Discount</span>
                    <div style="display: flex; align-items: center; gap: 4px;">
                        <span style="font-size: 11px; color: var(--text-muted);">Rs.</span>
                        <input type="number" id="posDiscount" value="0" min="0" oninput="recalcPos()" style="width: 75px; padding: 4px 8px; border-radius: 8px; border: 1px solid var(--border-subtle); text-align: right; font-weight: 700; outline: none;">
                    </div>
                </div>
                <div class="pos-math-row grand-total">
                    <span style="font-size: 16px; font-weight: 800; color: var(--text-heading);">Total Due</span>
                    <span class="pos-total-amount" id="posGrandTotal">Rs. 0</span>
                </div>

                <!-- Payment Method Strip -->
                <div class="pos-pay-strip">
                    <button type="button" class="pos-pay-btn active" onclick="setPayMethod('cash', this)">💵 Cash</button>
                    <button type="button" class="pos-pay-btn" onclick="setPayMethod('jazzcash', this)">📱 Raast / QR</button>
                    <button type="button" class="pos-pay-btn" onclick="setPayMethod('card', this)">💳 Card</button>
                </div>

                <!-- Cash Tendered Box -->
                <div class="pos-tender-box" id="posTenderBox">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 10px; font-weight: 800; color: var(--text-muted); margin-bottom: 2px;">CASH TENDERED</label>
                        <input type="number" id="posTendered" class="pos-tender-input" placeholder="0" oninput="calcChange()">
                    </div>
                    <div style="flex: 1; text-align: right;">
                        <label style="display: block; font-size: 10px; font-weight: 800; color: var(--text-muted); margin-bottom: 2px;">CHANGE TO RETURN</label>
                        <span class="pos-change-val" id="posChangeVal">Rs. 0</span>
                    </div>
                </div>

                <!-- Complete & Print Action Button -->
                <button type="button" id="btnPunchOrder" class="pos-punch-btn" onclick="submitPosBill()">
                    <span>⚡ Complete & Print Bill (Enter)</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                </button>
            </div>
        </aside>

    </main>

    <!-- Mobile Floating Quick-Jump to Slip -->
    <button type="button" id="posMobileSlipJump" class="pos-mobile-slip-jump" onclick="scrollToSlip()">
        <span id="posMobileSlipCount">🧾 View Slip (0)</span>
        <span id="posMobileSlipTotal">Rs. 0 →</span>
    </button>

    <!-- ── DEDICATED SIZE SELECTION MODAL ("SIZE SESSION") ── -->
    <div id="posSizeModal" class="pos-size-modal-backdrop" style="display: none;" onclick="if(event.target===this) closeSizeModal()">
        <div class="pos-size-modal-card">
            <div class="pos-size-modal-header">
                <div>
                    <span class="pos-size-modal-cat" id="sizeModalCat">Pizza</span>
                    <h3 class="pos-size-modal-title" id="sizeModalTitle">Dish Name</h3>
                </div>
                <button type="button" class="pos-size-modal-close" onclick="closeSizeModal()" title="Close">✕</button>
            </div>
            <p class="pos-size-modal-sub">Select size for this order item:</p>
            <div class="pos-size-modal-grid" id="sizeModalOptions">
                <!-- Dynamically populated options -->
            </div>
            <div class="pos-size-modal-footer">
                <span style="font-size: 11.5px; color: var(--text-muted);">Tip: Press keys 1, 2, 3 or Esc to cancel</span>
                <button type="button" class="pos-size-modal-cancel" onclick="closeSizeModal()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- ── CLIENT LOGIC ── -->
    <script>
    const posCatalog = @json($menuItems);
    let cart = [];
    let currentMode = 'takeaway';
    let currentPay = 'cash';
    let activeCategory = 'all';

    // Live Clock
    function updateClock() {
        const el = document.getElementById('posClock');
        if (el) {
            const now = new Date();
            el.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Mode switch
    function switchPosMode(mode, btn) {
        currentMode = mode;
        document.querySelectorAll('.pos-mode-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        const tableWrap = document.getElementById('posTableFieldWrap');
        if (tableWrap) {
            tableWrap.style.display = (mode === 'dine_in') ? 'block' : 'none';
            if (mode === 'dine_in') document.getElementById('posTableNumber').focus();
        }
    }

    // Category Filter
    function filterCategory(catId, btn) {
        activeCategory = String(catId);
        document.querySelectorAll('.pos-cat-pill').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        applyDishFilter();
    }

    // Search input
    function handlePosSearch() {
        const query = (document.getElementById('posSearch').value || '').trim();
        document.getElementById('posSearchClear').style.display = query ? 'block' : 'none';
        applyDishFilter();
    }

    function clearPosSearch() {
        const s = document.getElementById('posSearch');
        s.value = '';
        s.focus();
        document.getElementById('posSearchClear').style.display = 'none';
        applyDishFilter();
    }

    function applyDishFilter() {
        const query = (document.getElementById('posSearch').value || '').toLowerCase().trim();
        document.querySelectorAll('.pos-dish-card').forEach(card => {
            const name = (card.getAttribute('data-name') || '').toLowerCase();
            const cat = String(card.getAttribute('data-cat') || '');

            const matchQuery = !query || name.includes(query);
            const matchCat   = (activeCategory === 'all') || (cat === activeCategory);

            card.style.display = (matchQuery && matchCat) ? 'flex' : 'none';
        });
    }

    // Add dish
    let currentSizeDish = null;

    function onDishCardClick(dishId) {
        const dish = posCatalog.find(d => d.id === dishId);
        if (!dish) return;

        if (dish.sizes && Array.isArray(dish.sizes) && dish.sizes.length > 0) {
            openSizePickerModal(dish);
        } else {
            addDishDirect(dish.id, dish.name, parseFloat(dish.price || 0), null);
        }
    }

    function openSizePickerModal(dish) {
        currentSizeDish = dish;
        const modal = document.getElementById('posSizeModal');
        const catEl = document.getElementById('sizeModalCat');
        const titleEl = document.getElementById('sizeModalTitle');
        const gridEl = document.getElementById('sizeModalOptions');

        if (!modal || !dish) return;

        if (catEl) catEl.textContent = dish.category?.name || 'Dish';
        if (titleEl) titleEl.textContent = dish.name;

        let optionsHtml = '';
        (dish.sizes || []).forEach((s, idx) => {
            const sizeName = s.size || s.name || `Size ${idx + 1}`;
            const sizePrice = Number(s.price) || 0;
            const shortcutNum = idx + 1 <= 9 ? idx + 1 : '';

            optionsHtml += `
                <div class="pos-size-option-card" onclick="selectSizeAndAdd(${idx})" title="Add ${escapeHtml(sizeName)} (Rs. ${sizePrice.toLocaleString()})">
                    ${shortcutNum ? `<span class="pos-size-option-shortcut">Press ${shortcutNum}</span>` : ''}
                    <span class="pos-size-option-name">${escapeHtml(sizeName)}</span>
                    <span class="pos-size-option-price">Rs. ${sizePrice.toLocaleString()}</span>
                </div>
            `;
        });

        gridEl.innerHTML = optionsHtml;
        modal.style.display = 'flex';
    }

    function selectSizeAndAdd(idx) {
        if (!currentSizeDish || !currentSizeDish.sizes || !currentSizeDish.sizes[idx]) return;
        const s = currentSizeDish.sizes[idx];
        addDishDirect(
            currentSizeDish.id,
            currentSizeDish.name,
            Number(s.price) || 0,
            s.size || s.name || ''
        );
        closeSizeModal();
    }

    function closeSizeModal() {
        const modal = document.getElementById('posSizeModal');
        if (modal) modal.style.display = 'none';
        currentSizeDish = null;
    }

    function addDishDirect(id, name, price, size) {
        const key = `${id}_${size || 'def'}`;
        const item = cart.find(i => i.key === key);

        if (item) {
            item.qty += 1;
        } else {
            cart.push({
                key: key,
                id: id,
                name: name,
                price: Number(price) || 0,
                size: size || null,
                qty: 1
            });
        }

        renderCart();
    }

    function modifyQty(idx, delta) {
        if (!cart[idx]) return;
        cart[idx].qty += delta;
        if (cart[idx].qty <= 0) {
            cart.splice(idx, 1);
        }
        renderCart();
    }

    function deleteCartItem(idx) {
        if (cart[idx]) {
            cart.splice(idx, 1);
            renderCart();
        }
    }

    function renderCart() {
        const list = document.getElementById('posSlipItems');
        if (!list) return;

        if (cart.length === 0) {
            list.innerHTML = `
                <div class="pos-empty-slip">
                    <div class="pos-empty-icon">🧾</div>
                    <div class="pos-empty-h">Slip is empty</div>
                    <p class="pos-empty-p">Click any dish on the left to add items to this counter bill.</p>
                </div>
            `;
        } else {
            let html = '';
            cart.forEach((item, idx) => {
                const sub = item.price * item.qty;
                const sizeBadge = item.size ? `<span style="font-size: 10px; background: #ffffff; padding: 2px 6px; border-radius: 4px; border: 1px solid var(--border-subtle); margin-left: 4px;">${escapeHtml(item.size)}</span>` : '';

                html += `
                    <div class="pos-cart-row">
                        <div class="pos-cart-info">
                            <div class="pos-cart-name">${escapeHtml(item.name)} ${sizeBadge}</div>
                            <div class="pos-cart-price-sub">Rs. ${item.price.toLocaleString()} each</div>
                        </div>
                        <div class="pos-cart-stepper">
                            <button type="button" class="pos-stepper-btn" onclick="modifyQty(${idx}, -1)">−</button>
                            <span class="pos-stepper-qty">${item.qty}</span>
                            <button type="button" class="pos-stepper-btn" onclick="modifyQty(${idx}, 1)">+</button>
                        </div>
                        <div class="pos-cart-total">Rs. ${sub.toLocaleString()}</div>
                        <button type="button" class="pos-cart-del" onclick="deleteCartItem(${idx})" title="Remove item">✕</button>
                    </div>
                `;
            });
            list.innerHTML = html;
        }

        recalcPos();

        // Update mobile jump pill
        const jumpBtn = document.getElementById('posMobileSlipJump');
        if (jumpBtn) {
            if (cart.length > 0) {
                jumpBtn.style.display = 'flex';
                const totalItems = cart.reduce((a, b) => a + b.qty, 0);
                const sub = cart.reduce((acc, i) => acc + (i.price * i.qty), 0);
                const countEl = document.getElementById('posMobileSlipCount');
                const totalEl = document.getElementById('posMobileSlipTotal');
                if (countEl) countEl.textContent = `🧾 View Slip (${totalItems})`;
                if (totalEl) totalEl.textContent = `Rs. ${sub.toLocaleString()} →`;
            } else {
                jumpBtn.style.display = 'none';
            }
        }
    }

    function scrollToSlip() {
        const slip = document.querySelector('.pos-slip-panel');
        if (slip) slip.scrollIntoView({ behavior: 'smooth' });
    }

    function recalcPos() {
        const sub = cart.reduce((acc, i) => acc + (i.price * i.qty), 0);
        const disc = Math.max(0, parseFloat(document.getElementById('posDiscount')?.value || 0));
        const grand = Math.max(0, sub - disc);

        const subEl = document.getElementById('posSubtotalVal');
        if (subEl) subEl.textContent = 'Rs. ' + sub.toLocaleString();

        const grandEl = document.getElementById('posGrandTotal');
        if (grandEl) grandEl.textContent = 'Rs. ' + grand.toLocaleString();

        calcChange();
    }

    function setPayMethod(mode, btn) {
        currentPay = mode;
        document.querySelectorAll('.pos-pay-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        const tenderBox = document.getElementById('posTenderBox');
        if (tenderBox) {
            tenderBox.style.opacity = (mode === 'cash') ? '1' : '0.4';
        }
    }

    function calcChange() {
        const sub = cart.reduce((acc, i) => acc + (i.price * i.qty), 0);
        const disc = Math.max(0, parseFloat(document.getElementById('posDiscount')?.value || 0));
        const grand = Math.max(0, sub - disc);

        const tendered = parseFloat(document.getElementById('posTendered')?.value || 0);
        const change = Math.max(0, tendered - grand);

        const chgEl = document.getElementById('posChangeVal');
        if (chgEl) {
            chgEl.textContent = 'Rs. ' + change.toLocaleString();
            chgEl.style.color = (tendered >= grand && grand > 0) ? 'var(--color-green)' : 'var(--text-muted)';
        }
    }

    // Submit Bill & Auto Print
    async function submitPosBill() {
        if (cart.length === 0) {
            alert('Please select at least 1 menu item to punch.');
            return;
        }

        const btn = document.getElementById('btnPunchOrder');
        btn.disabled = true;
        btn.innerHTML = '<span>⏳ Printing & Saving Order...</span>';

        try {
            const payload = {
                order_type: currentMode,
                table_number: currentMode === 'dine_in' ? document.getElementById('posTableNumber')?.value : null,
                customer_name: document.getElementById('posCustomerName')?.value || 'Counter Guest',
                customer_phone: document.getElementById('posCustomerPhone')?.value || '',
                payment_method: currentPay,
                discount: parseFloat(document.getElementById('posDiscount')?.value || 0),
                items: cart.map(i => ({
                    id: i.id,
                    name: i.name,
                    price: i.price,
                    size: i.size,
                    quantity: i.qty
                }))
            };

            const res = await fetch("{{ route('dashboard.pos.quick-order', $restaurant->id) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (data && data.success) {
                // Instantly open thermal print bill window (auto-invokes window.print() inside print-bill.blade.php)
                if (data.print_url) {
                    const printWin = window.open(data.print_url, '_blank', 'width=460,height=720');
                    if (printWin) printWin.focus();
                }

                // Reset slip
                cart = [];
                renderCart();
                document.getElementById('posTendered').value = '';
                calcChange();

                // Increment sequence
                const seqBadge = document.getElementById('posSeqBadge');
                if (seqBadge) {
                    seqBadge.textContent = '#' + (Number(data.daily_number) + 1);
                }

                showToast(`✓ Order #${data.daily_number} printed and handed over!`);
            } else {
                alert(data.error || data.message || 'Failed to complete order.');
            }
        } catch (e) {
            console.error(e);
            alert('An unexpected error occurred while placing order.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span>⚡ Complete & Print Bill (Enter)</span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>';
        }
    }

    function showToast(msg) {
        const t = document.createElement('div');
        t.className = 'pos-toast';
        t.innerHTML = `<span>🧾</span> <span>${escapeHtml(msg)}</span>`;
        document.body.appendChild(t);
        setTimeout(() => {
            t.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            t.style.opacity = '0';
            t.style.transform = 'translateX(-50%) translateY(10px)';
            setTimeout(() => t.remove(), 300);
        }, 3000);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' })[m]);
    }

    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        // Size Modal keyboard shortcuts
        const sizeModal = document.getElementById('posSizeModal');
        if (sizeModal && sizeModal.style.display === 'flex' && currentSizeDish) {
            if (e.key === 'Escape') {
                e.preventDefault();
                closeSizeModal();
                return;
            }
            const num = parseInt(e.key);
            if (!isNaN(num) && num >= 1 && num <= (currentSizeDish.sizes || []).length) {
                e.preventDefault();
                selectSizeAndAdd(num - 1);
                return;
            }
        }

        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey || document.activeElement.tagName !== 'INPUT')) {
            e.preventDefault();
            submitPosBill();
        }
        if (e.key === 'Escape') {
            if (cart.length > 0 && confirm('Clear current slip?')) {
                cart = [];
                renderCart();
            }
        }
    });
    </script>
</body>
</html>
