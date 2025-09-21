import ArchieIcon from '../Components/Icons/Archie';
import CampaignSelector from '../Components/Blocks/CampaignSelector';

const { __ } = wp.i18n;

/**
 * Get the settings to register the campaign selector block.
 *
 * Because we need to support older versions (<=5.7) this returns the
 * proper settings. Versions >=5.8 use the block.json approach.
 *
 * @since 2.6.10
 *
 * @returns {Object} The settings for the campaign selector block.
 */
export const getBlockSettings = () => {
	const wpVersion = parseFloat(OMAPI.wpVersion);
	const baseSettings = {
		icon: ArchieIcon,
		edit: CampaignSelector,
		save() {
			return null;
		},
	};
	const legacySettings = {
		title: OMAPI.i18n.title,
		description: OMAPI.i18n.description,
		category: 'embed',
		keywords: [
			__('Popup', 'optin-monster-api'),
			__('Form', 'optin-monster-api'),
			__('Campaign', 'optin-monster-api'),
			__('Email', 'optin-monster-api'),
			__('Conversion', 'optin-monster-api'),
		],
		attributes: {
			slug: {
				type: 'string',
			},
			followrules: {
				type: 'boolean',
				default: true,
			},
		},
	};

	return wpVersion >= 5.8 ? baseSettings : Object.assign(baseSettings, legacySettings);
};
