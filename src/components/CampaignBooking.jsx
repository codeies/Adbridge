import React from 'react';
import { Circle, Radio, Tv, Clock, Calendar, CreditCard, FileVideo, FileAudio, CheckCircle } from 'lucide-react';
import { Card, CardContent } from "@/components/ui/card";
import useCampaignStore from "@/stores/useCampaignStore";
import BillboardStep from "@/components/BillboardStep";
import RadioStep from "@/components/RadioStep";
import TVStep from "@/components/TVStep";
import BBDurationStep from "@/components/Billboard/BBDurationStep";
import BBDateStep from "@/components/Billboard/BBDateStep";
import RDDateStep from './Radio/RDDateStep';
import PaymentStep from './PaymentStep';

const CampaignBooking = () => {
    const { currentStep, campaignType, setCurrentStep, setCampaignType } = useCampaignStore();

    const campaignSteps = {
        billboard: [
            { name: 'Campaign Type', icon: Circle },
            { name: 'Select Billboard', icon: Circle },
            { name: 'Duration', icon: Clock },
            { name: 'Schedule', icon: Calendar },
            { name: 'Payment', icon: CreditCard }
        ],
        radio: [
            { name: 'Campaign Type', icon: Circle },
            { name: 'Select Station', icon: Radio },
            { name: 'Time Slot', icon: Clock },
            { name: 'Script Upload', icon: FileAudio },
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

    const getCurrentSteps = () => {
        if (!campaignType) return campaignSteps.billboard;
        return campaignSteps[campaignType];
    };

    const handleCampaignSelect = (type) => {
        setCampaignType(type);
        setCurrentStep(2);
    };

    const renderStep1 = () => (
        <div className="grid grid-cols-3 gap-4">
            <Card
                className="p-6 cursor-pointer hover:bg-gray-50"
                onClick={() => handleCampaignSelect('billboard')}
            >
                <CardContent className="flex flex-col items-center space-y-4">
                    <div className="text-4xl">🏢</div>
                    <h3 className="text-xl font-semibold">Billboard</h3>
                    <p className="text-gray-600 text-center">Traditional and digital billboard advertising</p>
                </CardContent>
            </Card>
            <Card
                className="p-6 cursor-pointer hover:bg-gray-50"
                onClick={() => handleCampaignSelect('radio')}
            >
                <CardContent className="flex flex-col items-center space-y-4">
                    <div className="text-4xl">📻</div>
                    <h3 className="text-xl font-semibold">Radio</h3>
                    <p className="text-gray-600 text-center">Radio station advertising spots</p>
                </CardContent>
            </Card>
            <Card
                className="p-6 cursor-pointer hover:bg-gray-50"
                onClick={() => handleCampaignSelect('tv')}
            >
                <CardContent className="flex flex-col items-center space-y-4">
                    <div className="text-4xl">📺</div>
                    <h3 className="text-xl font-semibold">TV</h3>
                    <p className="text-gray-600 text-center">Television advertising campaigns</p>
                </CardContent>
            </Card>
        </div>
    );

    const renderCurrentStep = () => {
        if (currentStep === 1) return renderStep1();

        switch (campaignType) {
            case 'billboard':
                if (currentStep === 2) return <BillboardStep />;
                if (currentStep === 3) return <BBDurationStep />;
                if (currentStep === 4) return <BBDateStep />;
                if (currentStep === 5) return <PaymentStep />;
                return null;

            case 'radio':
                if (currentStep === 2) return <RadioStep />;
                if (currentStep === 3) return <RDDateStep />;
                if (currentStep === 4) return <BBDateStep />;
                return null;

            case 'tv':
                return <TVStep />;

            default:
                return null;
        }
    };

    return (
        <div className="max-w-6xl mx-auto p-6">
            {/* Enhanced Progress Bar */}
            <div className="mb-8">
                <div className="relative">
                    <div className="h-1 bg-gray-200 absolute w-full top-5"></div>
                    <div
                        className="h-1 bg-black absolute transition-all duration-300 top-5"
                        style={{ width: `${(currentStep / getCurrentSteps().length) * 100}%` }}
                    ></div>

                    {/* Steps with Icons */}
                    <div className="relative flex justify-between">
                        {getCurrentSteps().map((step, index) => {
                            const StepIcon = step.icon;
                            const isCompleted = currentStep > index + 1;
                            const isCurrent = currentStep === index + 1;

                            return (
                                <div key={step.name} className="flex flex-col items-center">
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
                                        mt-2 text-sm font-medium
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

            {/* Step Content */}
            {renderCurrentStep()}
        </div>
    );
};

export default CampaignBooking;