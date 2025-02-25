import { create } from 'zustand';

const useCampaignStore = create((set) => ({
    currentStep: 1,
    campaignType: null,
    totalOrderCost: 0, // This will be the sum of billboard, radio, and arcon costs
    billboard: {
        selectedCategory: "all",
        selectedLocation: "all",
        searchTerm: "",
        selectedBillboard: null,
        selectedDuration: "",
        startDate: "",
        endDate: "",
        totalPrice: 0, // Separate price for billboard
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
        scriptType: null,
        selectedSession: "",
        selectedSpots: "",
        audioFile: null,
        announcement: "",
        jingleCreationType: "upload",
        jingleText: "",
        startDate: "",
        endDate: "",
        numberOfDays: "",
        totalCost: 0, // Separate price for radio
    },
    //    tv: {},
    arcon: {
        status: null,
        selectedPermit: null,
        permitFile: null,
        cost: 0, // Separate price for arcon
    },

    // Actions
    setCurrentStep: (step) => set({ currentStep: step }),
    setCampaignType: (type) => set({ campaignType: type }),

    // Billboard actions
    setBillboardFilters: (filters) => set((state) => ({
        billboard: { ...state.billboard, ...filters }
    })),
    setBillboardTotalPrice: (price) => set((state) => ({
        billboard: { ...state.billboard, totalPrice: parseFloat(price) },
        totalOrderCost: state.radio.totalCost + state.arcon.cost + parseFloat(price), // Update totalOrderCost
    })),

    // Radio actions
    setRadioFilters: (filters) => set((state) => {
        const updatedRadio = { ...state.radio, ...filters };
        return { radio: updatedRadio };
    }),
    setRadioTotalCost: (cost) => set((state) => ({
        radio: { ...state.radio, totalCost: parseFloat(cost) },
        totalOrderCost: state.billboard.totalPrice + state.arcon.cost + parseFloat(cost), // Update totalOrderCost
    })),

    // Arcon actions
    setArconDetails: (details) => set((state) => ({
        arcon: { ...state.arcon, ...details },
        totalOrderCost: state.billboard.totalPrice + state.radio.totalCost + (details.cost || 0), // Update totalOrderCost
    })),

    // Utility function to calculate total order cost
    calculateTotalOrderCost: () => set((state) => ({
        totalOrderCost: state.billboard.totalPrice + state.radio.totalCost + state.arcon.cost,
    })),
}));

export default useCampaignStore;