import { create } from 'zustand';

const useCampaignStore = create((set) => ({
    currentStep: 1,
    campaignType: null,
    billboard: {
        selectedCategory: "all",
        selectedLocation: "all",
        searchTerm: "",
        selectedBillboard: null,
        selectedDuration: ""
    },
    radio: {
        selectedCategory: "all",
        selectedLocation: "all",
        searchTerm: "",
        selectedStation: null,
        selectedDuration: "",
        selectedTimeSlot: "",
        jingles: [],
        announcements: [],
        pricing: {} // Ensure this exists
    },
    tv: {},

    // Actions
    setCurrentStep: (step) => set({ currentStep: step }),
    setCampaignType: (type) => set({ campaignType: type }),
    setBillboardFilters: (filters) => set((state) => ({
        billboard: { ...state.billboard, ...filters }
    })),
    setRadioFilters: (filters) => set((state) => ({
        radio: { ...state.radio, ...filters }
    })),
    setSelectedRadio: (radio) => {
        // Extracting pricing from jingles and announcements
        const jinglePricing = radio.jingles?.map(j => ({
            name: j.name,
            pricePerSlot: parseFloat(j.price_per_slot),
            maxSlots: parseInt(j.max_slots, 10)
        })) || [];

        const announcementPricing = radio.announcements?.map(a => ({
            name: a.name,
            pricePerSlot: parseFloat(a.price_per_slot),
            maxSlots: parseInt(a.max_slots, 10)
        })) || [];

        set({
            radio: {
                selectedStation: radio.title,
                jingles: jinglePricing,
                announcements: announcementPricing,
                pricing: {
                    jingles: jinglePricing,
                    announcements: announcementPricing
                }
            }
        });
    }
}));

export default useCampaignStore;
