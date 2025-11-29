/**
 * Install plugin.
 */

import Util from './util';
import formatDate from './date';
import formatNumber from './number';
import formatCurrency from './currency';
import relativeDate from './relative';
import defaultLocale from '../dist/locales/en.json';

function plugin(Vue) {

    var vue = Vue.prototype;

    if (!vue.$locale) {
        vue.$locale = defaultLocale;
    }

    Util(Vue);

    vue.$date = formatDate;
    vue.$number = formatNumber;
    vue.$currency = formatCurrency;
    vue.$relativeDate = relativeDate;

    Vue.filter('date', (date, format, timezone) => vue.$date(date, format, timezone));
    Vue.filter('number', (number, fractionSize) => vue.$number(number, fractionSize));
    Vue.filter('currency', (amount, currencySymbol, fractionSize) => vue.$currency(amount, currencySymbol, fractionSize));
    Vue.filter('relativeDate', (date, options) => vue.$relativeDate(date, options));
}

if (typeof window !== 'undefined' && window.Vue) {
    window.Vue.use(plugin);
}

export default plugin;
