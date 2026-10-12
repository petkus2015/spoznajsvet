// Ktoré časti Natural Earth zlúčiť do jedného celku (politické regióny, duplicity, exklávy) – ostrovy ostávajú samostatné.
module.exports = {
  merge: {
    BFR: 'BEL', BWR: 'BEL', BCR: 'BEL', SRV: 'SRS', BIS: 'BHF', BHB: 'BHF', IRK: 'IRR', SOP: 'SOX', SOL: 'SOX',
    GEA: 'GEG', RUE: 'RUA', KXI: 'KOX', KNX: 'KOX', CYN: 'CYP', CNM: 'CYP', ESB: 'CYP', WSB: 'CYP',
    SEM: 'ESX', SEC: 'ESX', USG: 'CUB', RUC: 'UKR', GAZ: 'WEB', TLP: 'TLX', NZN: 'NZS',
    JPX: 'JPH', JPY: 'JPH', JPS: 'JPH', JPK: 'JPH', KAB: 'KAZ', BRI: 'BRA',
  },
  // Spoločné údaje zlúčeného celku.
  head: {
    BEL: ['BE', 'Belgium'], SRS: ['RS', 'Serbia'], BHF: ['BA', 'Bosnia and Herzegovina'], IRR: ['IQ', 'Iraq'],
    SOX: ['SO', 'Somalia'], GEG: ['GE', 'Georgia'], RUA: ['RU', 'Russia'], KOX: ['KR', 'South Korea'], CYP: ['CY', 'Cyprus'],
    ESX: ['ES', 'Spain'], CUB: ['CU', 'Cuba'], UKR: ['UA', 'Ukraine'], WEB: ['PS', 'Palestine'], TLX: ['TL', 'East Timor'],
    NZS: ['NZ', 'New Zealand'], JPH: ['JP', 'Japan'], KAZ: ['KZ', 'Kazakhstan'], BRA: ['BR', 'Brazil'], KNZ: ['KP', 'North Korea'],
  },
};
