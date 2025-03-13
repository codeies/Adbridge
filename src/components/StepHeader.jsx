import { Button } from "@/components/ui/button";

const StepHeader = ({ title, onBack }) => {
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
            </div>
        </div>
    );
};

export default StepHeader;
