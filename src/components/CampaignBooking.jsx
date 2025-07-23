import React, { useEffect, useState } from 'react';
import { Circle, Radio, Tv, Clock, Calendar, CreditCard, FileVideo, FileAudio, CheckCircle, BadgeCheck, RefreshCw } from 'lucide-react';
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import useCampaignStore from "@/stores/useCampaignStore";
import BillboardStep from "@/components/BillboardStep";
import RadioStep from "@/components/RadioStep";
import TVStep from "@/components/TVStep";
import BBDurationStep from "@/components/Billboard/BBDurationStep";
import BBDateStep from "@/components/Billboard/BBDateStep";
import RDDateStep from './Radio/RDDateStep';
import PaymentStep from './PaymentStep';
import RadioArconStep from './Radio/RDArconStep';
import RDArconStep from './Radio/RDArconStep';

const CampaignBooking = () => {
    const {
        currentStep,
        campaignType,
        setCurrentStep,
        setCampaignType,
        hasSavedCampaign,
        loadFromLocalStorage,
        resetCampaign
    } = useCampaignStore();

    const campaignSteps = {
        billboard: [
            { name: 'Campaign Type', icon: Circle },
            { name: 'Select Billboard', icon: Circle },
            { name: 'Duration', icon: Clock },
            { name: 'Schedule', icon: Calendar },
            { name: 'ARCON Permit', icon: BadgeCheck },
            { name: 'Payment', icon: CreditCard }
        ],
        radio: [
            { name: 'Campaign Type', icon: Circle },
            { name: 'Select Station', icon: Radio },
            { name: 'Script Upload', icon: FileAudio },
            { name: 'ARCON Permit', icon: BadgeCheck },
            { name: 'Payment', icon: CreditCard }
        ],
        tv: [
            { name: 'Campaign Type', icon: Circle },
            { name: 'Channel Selection', icon: Tv },
            { name: 'Time Slot', icon: Clock },
            { name: 'Video Upload', icon: FileVideo },
            { name: 'Review', icon: CheckCircle },
            { name: 'Payment', icon: CreditCard }
        ]
    };

    const [isResumed, setIsResumed] = useState(false);

    const handleResumeCampaign = () => {
        const loaded = loadFromLocalStorage();
        if (loaded) {
            // Automatically set the step based on the loaded campaign
            setCurrentStep(currentStep);
            setIsResumed(true);
        }
    };

    const handleStartNewCampaign = () => {
        resetCampaign();
    };

    const getCurrentSteps = () => {
        if (!campaignType) return campaignSteps.billboard;
        return campaignSteps[campaignType];
    };

    const handleCampaignSelect = (type) => {
        setCampaignType(type);
        setCurrentStep(2);
    };

    const renderStep1 = () => {
        // Check if there's a saved campaign


        return (
            <div className="space-y-6">
                {/* Resume Campaign Banner */}
                {/*                 {savedCampaign && (
                    <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4 flex items-center justify-between">
                        <div className="flex items-center space-x-4">
                            <RefreshCw className="text-yellow-600 w-6 h-6" />
                            <div>
                                <p className="text-yellow-700 font-semibold">You have an unfinished campaign</p>
                                <p className="text-yellow-600 text-sm">Would you like to resume or start a new one?</p>
                            </div>
                        </div>
                        <div className="flex space-x-2">
                            <Button
                                variant="outline"
                                onClick={handleResumeCampaign}
                                className="px-4 py-2"
                            >
                                Resume Campaign
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={handleStartNewCampaign}
                                className="px-4 py-2"
                            >
                                Start New
                            </Button>
                        </div>
                    </div>
                )} */}

                {/* Campaign Type Selection */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Card
                        className="p-6 cursor-pointer hover:bg-gray-50"
                        onClick={() => handleCampaignSelect('billboard')}
                    >
                        <CardContent className="flex flex-col items-center space-y-4">
                            <div className="text-3xl md:text-4xl">🏢</div>
                            <h3 className="text-lg md:text-xl font-semibold">Billboard</h3>
                            <p className="text-gray-600 text-center text-sm md:text-base">Traditional and digital billboard advertising</p>
                        </CardContent>
                    </Card>
                    <Card
                        className="p-6 cursor-pointer hover:bg-gray-50"
                        onClick={() => handleCampaignSelect('radio')}
                    >
                        <CardContent className="flex flex-col items-center space-y-4">
                            <div className="text-3xl md:text-4xl">📻</div>
                            <h3 className="text-lg md:text-xl font-semibold">Radio</h3>
                            <p className="text-gray-600 text-center text-sm md:text-base">Radio station advertising spots</p>
                        </CardContent>
                    </Card>
                    <Card
                        className="p-6 cursor-pointer hover:bg-gray-50"
                        onClick={() => handleCampaignSelect('tv')}
                    >
                        <CardContent className="flex flex-col items-center space-y-4">
                            <div className="text-3xl md:text-4xl">📺</div>
                            <h3 className="text-lg md:text-xl font-semibold">TV</h3>
                            <p className="text-gray-600 text-center text-sm md:text-base">Television advertising campaigns</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        );
    };

    const renderCurrentStep = () => {
        const savedCampaign = hasSavedCampaign();
        /*         if (savedCampaign && (!isResumed)) {
                    return (
                        <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4 flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                <RefreshCw className="text-yellow-600 w-6 h-6" />
                                <div>
                                    <p className="text-yellow-700 font-semibold">You have an unfinished campaign</p>
                                    <p className="text-yellow-600 text-sm">Would you like to resume or start a new one?</p>
                                </div>
                            </div>
                            <div className="flex space-x-2">
                                <Button
                                    variant="outline"
                                    onClick={handleResumeCampaign}
                                    className="px-4 py-2"
                                >
                                    Resume Campaign
                                </Button>
                                <Button
                                    variant="destructive"
                                    onClick={handleStartNewCampaign}
                                    className="px-4 py-2"
                                >
                                    Start New
                                </Button>
                            </div>
                        </div>
                    );
                } */
        if (currentStep === 1) return renderStep1();

        switch (campaignType) {
            case 'billboard':
                if (currentStep === 2) return <BillboardStep />;
                if (currentStep === 3) return <BBDurationStep />;
                if (currentStep === 4) return <BBDateStep />;
                if (currentStep === 5) return <RDArconStep />;
                if (currentStep === 6) return <PaymentStep />;
                return null;

            case 'radio':
            case 'tv':
                if (currentStep === 2) return <RadioStep />;
                if (currentStep === 3) return <RDDateStep />;
                if (currentStep === 4) return <RDArconStep />;
                if (currentStep === 5) return <PaymentStep />;
                return null;

            default:
                return null;
        }
    };

    return (
        <div className="max-w-6xl mx-auto p-6">

            {/* Enhanced Responsive Progress Bar with Horizontal Scroll on Mobile */}
            <div className="mb-8 w-full">

                <div className="relative overflow-x-auto pb-2">
                    <div className="min-w-max">
                        {/* Background track - always visible */}
                        <div className="h-1 bg-gray-200 absolute w-full top-5"></div>

                        {/* Progress fill - always visible */}
                        <div
                            className="h-1 bg-black absolute transition-all duration-300 top-5"
                        ></div>

                        {/* Steps with Icons - Always horizontal with minimum width to prevent squishing */}
                        <div className="relative flex justify-between" style={{ minWidth: getCurrentSteps().length * 100 + 'px' }}>
                            {getCurrentSteps().map((step, index) => {
                                const StepIcon = step.icon;
                                const isCompleted = currentStep > index + 1;
                                const isCurrent = currentStep === index + 1;

                                return (
                                    <div key={step.name} className="flex flex-col items-center px-2">
                                        <div className={`
                                w-10 h-10 rounded-full flex items-center justify-center
                                transition-all duration-300 relative z-10
                                ${isCompleted ? 'bg-black text-white' :
                                                isCurrent ? 'bg-black text-white' :
                                                    'bg-white border-2 border-gray-200 text-gray-400'}
                            `}>
                                            <StepIcon className="w-5 h-5" />
                                        </div>
                                        <span className={`
                                mt-2 text-sm font-medium whitespace-nowrap
                                ${isCompleted || isCurrent ? 'text-black' : 'text-gray-400'}
                            `}>
                                            {step.name}
                                        </span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>
            {/* Step Content */}
            {renderCurrentStep()}
        </div>
    );
};

export default CampaignBooking;