import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';

const initialState = {
    currentStep: 1,
    campaignType: null,
    totalOrderCost: 0,
    featured_image: false,
    billboard: {
        selectedCategory: "all",
        selectedLocation: "all",
        searchTerm: "",
        selectedBillboard: null,
        selectedDuration: "",
        startDate: "",
        endDate: "",
        totalCost: 0,
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
        mediaType: "audio",
        mediaUrl: null,
        mediaFile: null,
        announcement: "",
        jingleCreationType: "upload",
        jingleText: "",
        startDate: "",
        endDate: "",
        numberOfDays: "",
        totalCost: 0,
    },
    arcon: {
        status: null,
        selectedPermit: null,
        permitFile: null,
        cost: 0,
    }
};

const useCampaignStore = create(
    persist(
        (set, get) => ({
            ...initialState,

            // Actions
            setCurrentStep: (step) => {
                set({ currentStep: step });
                get().saveToLocalStorage();
            },
            setCampaignType: (type) => {
                set({ campaignType: type });
                get().saveToLocalStorage();
            },

            // Billboard actions
            setBillboardFilters: (filters) => {
                set((state) => ({
                    billboard: { ...state.billboard, ...filters }
                }));
                get().saveToLocalStorage();
            },
            setBillboardTotalPrice: (price) => {
                set((state) => ({
                    radio: { ...state.radio, totalCost: 0 },
                    billboard: { ...state.billboard, totalCost: parseFloat(price) },
                    totalOrderCost: state.billboard.totalCost + state.arcon.cost + parseFloat(price),
                }));
                get().saveToLocalStorage();
            },

            // Radio actions
            setRadioFilters: (filters) => {
                set((state) => {
                    const updatedRadio = { ...state.radio, ...filters };
                    return { radio: updatedRadio };
                });
                get().saveToLocalStorage();
            },
            setRadioTotalCost: (cost) => {
                set((state) => ({
                    radio: { ...state.radio, totalCost: parseFloat(cost) },
                    billboard: { ...state.billboard, totalCost: 0 },
                    totalOrderCost: state.radio.totalCost + state.arcon.cost + parseFloat(cost),
                }));
                get().saveToLocalStorage();
            },

            // Arcon actions
            setArconDetails: (details) => {
                set((state) => ({
                    arcon: { ...state.arcon, ...details },
                    totalOrderCost: state.billboard.totalCost + state.radio.totalCost + (details.cost || 0),
                }));
                get().saveToLocalStorage();
            },

            // Utility function to calculate total order cost
            calculateTotalOrderCost: () => {
                set((state) => ({
                    totalOrderCost: state.billboard.totalCost + state.radio.totalCost + state.arcon.cost,
                }));
                get().saveToLocalStorage();
            },

            // Save current state to local storage
            saveToLocalStorage: () => {
                const state = get();
                localStorage.setItem('campaign-booking-state', JSON.stringify(state));
            },

            // Load state from local storage
            loadFromLocalStorage: () => {
                const savedState = localStorage.getItem('campaign-booking-state');
                if (savedState) {
                    const parsedState = JSON.parse(savedState);
                    set(parsedState);
                    return true;
                }
                return false;
            },

            // Reset function to clear the campaign state and start a new campaign
            resetCampaign: () => {
                localStorage.removeItem('campaign-booking-state');
                set(() => ({
                    ...initialState,
                    // Keep the currentStep as 1 to start a new campaign
                    currentStep: 1,
                }));
            },

            // Check if there's a saved campaign
            hasSavedCampaign: () => {
                return !!localStorage.getItem('campaign-booking-state');
            },
        }),
        {
            name: 'campaign-booking-storage', // unique name
            storage: createJSONStorage(() => localStorage),
            // Optional: specify which parts of the state to persist
            partialize: (state) => ({
                currentStep: state.currentStep,
                campaignType: state.campaignType,
                totalOrderCost: state.totalOrderCost,
                billboard: state.billboard,
                radio: state.radio,
                arcon: state.arcon,
            }),
        }
    )
);

export default useCampaignStore;