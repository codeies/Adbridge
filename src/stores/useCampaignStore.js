import { create } from 'zustand';

const useCampaignStore = create((set) => ({
    currentStep: 1,
    campaignType: null,
    billboard: {
        selectedCategory: "all",
        selectedLocation: "all",
        searchTerm: "",
        selectedBillboard: null,
        selectedDuration: "",
        startDate: "",
        endDate: "",
        totalPrice: 0,
        mediaType: "image-video",
        mediaUrl: null,
        mediaFile: null,
        numDays: 1,
        numWeeks: 1,
        numMonths: 1,
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
        pricing: {},
        campaignType: null,
        selectedSession: "",
        selectedSpots: "",
        audioFile: null,
        announcement: "",
        jingleCreationType: "upload",
        jingleText: "",
        startDate: "",
        numberOfDays: "",
    },
    tv: {},

    totalOrderCost: 0,

    setCurrentStep: (step) => set({ currentStep: step }),
    setCampaignType: (type) => set({ campaignType: type }),
    setBillboardFilters: (filters) => set((state) => ({
        billboard: { ...state.billboard, ...filters } // Update billboardFilters instead of billboard
    })),
    setRadioFilters: (filters) => set((state) => ({
        radio: { ...state.radio, ...filters }
    })),
    setSelectedRadio: (radio) => {
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
    },
    setTotalOrderCost: (cost) => set({ totalOrderCost: cost })
}));

export default useCampaignStore;