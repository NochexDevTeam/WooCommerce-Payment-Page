const settings = window.wc.wcSettings.getSetting( 'wc_nochex_data', {} );
const label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'Pay by Credit / Debit Card (NOCHEX)', 'wc_nochex' );
const Content = () => {
    return window.wp.htmlEntities.decodeEntities( settings.description );
};
const NCX_Gateway = {
    name: 'wc_nochex',
    label: label,
    content: Object( window.wp.element.createElement )( Content, null ),
    edit: Object( window.wp.element.createElement )( Content, null ),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( NCX_Gateway );