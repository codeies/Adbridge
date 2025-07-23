import { Button } from "@/components/ui/button";
import useCampaignStore from "@/stores/useCampaignStore";

const StepHeader = ({ title, onBack }) => {
    const { resetCampaign } = useCampaignStore();

    const handleStartNewCampaign = () => {
        const isConfirmed = window.confirm("Are you sure you want to start over? This will reset all progress.");
        if (isConfirmed) {
            resetCampaign();
        }
    };

    return (
        <div className="w-full border-b pb-3 md:pb-4 mb-4 md:mb-6">
            <div className="flex items-center max-w-screen-xl mx-auto gap-x-4">
                <Button
                    variant="outline"
                    onClick={onBack}
                    className="flex items-center hover:bg-gray-100 text-xs md:text-sm px-2 py-1 md:px-3 md:py-2"
                >
                    ← Back
                </Button>
                <div className="text-base sm:text-lg md:text-xl font-semibold flex-1 text-center md:text-left">
                    {title}
                </div>
                <Button
                    variant="destructive"
                    onClick={handleStartNewCampaign}
                    className="px-4 py-2"
                >
                    Start Over
                </Button>
            </div>
        </div>
    );
};

export default StepHeader;
